<?php

use App\Domain\Enrollment\Exceptions\PaymentRequiredException;
use App\Domain\Enrollment\Services\EnrollmentService;
use App\Domain\Payment\Services\PaymentService;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;

beforeEach(function () {
    $this->learner = User::factory()->learner()->create();
    $this->admin = User::factory()->lmsAdmin()->create();
    $this->paymentService = app(PaymentService::class);
    $this->enrollmentService = app(EnrollmentService::class);

    // Enable commercial mode for payment tests
    config()->set('lms.mode', 'commercial');
});

describe('Payment Model', function () {
    it('generates unique invoice numbers', function () {
        Payment::factory()->create();
        $number1 = Payment::generateInvoiceNumber();

        Payment::factory()->create();
        $number2 = Payment::generateInvoiceNumber();

        expect($number1)->toStartWith('INV-');
        expect($number1)->not->toBe($number2);
    });

    it('checks pending status', function () {
        $payment = Payment::factory()->pending()->make();

        expect($payment->isPending())->toBeTrue();
        expect($payment->isPaid())->toBeFalse();
        expect($payment->canProcess())->toBeTrue();
    });

    it('checks paid status', function () {
        $payment = Payment::factory()->paid()->make();

        expect($payment->isPaid())->toBeTrue();
        expect($payment->isPending())->toBeFalse();
        expect($payment->canRefund())->toBeTrue();
    });

    it('checks failed status', function () {
        $payment = Payment::factory()->failed()->make();

        expect($payment->isFailed())->toBeTrue();
        expect($payment->canProcess())->toBeFalse();
    });

    it('marks payment as paid', function () {
        $payment = Payment::factory()->pending()->create();

        $payment->markPaid('TXN-123', 'credit_card');

        expect($payment->fresh()->isPaid())->toBeTrue();
        expect($payment->fresh()->gateway_transaction_id)->toBe('TXN-123');
        expect($payment->fresh()->paid_at)->not->toBeNull();
    });

    it('marks payment as failed', function () {
        $payment = Payment::factory()->pending()->create();

        $payment->markFailed('Insufficient funds');

        expect($payment->fresh()->isFailed())->toBeTrue();
        expect($payment->fresh()->metadata['failure_reason'])->toBe('Insufficient funds');
    });

    it('processes refund', function () {
        $payment = Payment::factory()->paid()->create(['amount' => 100000]);

        $payment->refund($this->admin, 100000, 'Customer request');

        expect($payment->fresh()->isRefunded())->toBeTrue();
        expect((float) $payment->fresh()->refund_amount)->toBe(100000.0);
        expect($payment->fresh()->refunded_by)->toBe($this->admin->id);
    });

    it('formats amount with currency', function () {
        $payment = Payment::factory()->make(['amount' => 150000, 'currency' => 'IDR']);

        expect($payment->getFormattedAmount())->toBe('150.000 IDR');
    });
});

describe('PaymentService', function () {
    describe('createCoursePayment', function () {
        it('creates payment for paid course', function () {
            $course = Course::factory()->published()->create([
                'is_paid' => true,
                'price' => 150000,
                'currency' => 'IDR',
            ]);

            $payment = $this->paymentService->createCoursePayment($this->learner, $course);

            expect($payment)->toBeInstanceOf(Payment::class);
            expect($payment->user_id)->toBe($this->learner->id);
            expect($payment->payable_type)->toBe(Course::class);
            expect($payment->payable_id)->toBe($course->id);
            expect((float) $payment->amount)->toBe(150000.0);
            expect($payment->status)->toBe(Payment::STATUS_PENDING);
            expect($payment->invoice_number)->toStartWith('INV-');
        });

        it('throws error for non-paid course', function () {
            $course = Course::factory()->published()->create(['is_paid' => false]);

            $this->paymentService->createCoursePayment($this->learner, $course);
        })->throws(RuntimeException::class, 'Course is not a paid course');

        it('throws error if already paid', function () {
            $course = Course::factory()->published()->create([
                'is_paid' => true,
                'price' => 100000,
            ]);

            Payment::factory()->paid()->create([
                'user_id' => $this->learner->id,
                'payable_type' => Course::class,
                'payable_id' => $course->id,
            ]);

            $this->paymentService->createCoursePayment($this->learner, $course);
        })->throws(RuntimeException::class, 'User has already paid');

        it('throws error if pending payment exists', function () {
            $course = Course::factory()->published()->create([
                'is_paid' => true,
                'price' => 100000,
            ]);

            Payment::factory()->pending()->create([
                'user_id' => $this->learner->id,
                'payable_type' => Course::class,
                'payable_id' => $course->id,
                'expires_at' => now()->addHours(24),
            ]);

            $this->paymentService->createCoursePayment($this->learner, $course);
        })->throws(RuntimeException::class, 'pending payment');
    });

    describe('handlePaymentSuccess', function () {
        it('marks payment as paid and creates enrollment', function () {
            $course = Course::factory()->published()->create([
                'is_paid' => true,
                'price' => 100000,
            ]);

            $payment = Payment::factory()->pending()->create([
                'user_id' => $this->learner->id,
                'payable_type' => Course::class,
                'payable_id' => $course->id,
            ]);

            $result = $this->paymentService->handlePaymentSuccess(
                $payment,
                'TXN-SUCCESS-123',
                'credit_card'
            );

            expect($result->isPaid())->toBeTrue();
            expect($result->enrollment_id)->not->toBeNull();

            // Verify enrollment was created
            $enrollment = Enrollment::find($result->enrollment_id);
            expect($enrollment)->not->toBeNull();
            expect($enrollment->user_id)->toBe($this->learner->id);
            expect($enrollment->course_id)->toBe($course->id);
        });

        it('does not re-process already paid payment', function () {
            $payment = Payment::factory()->paid()->create([
                'user_id' => $this->learner->id,
            ]);

            $result = $this->paymentService->handlePaymentSuccess($payment);

            // Should return without error, status unchanged
            expect($result->isPaid())->toBeTrue();
        });
    });

    describe('hasUserPaidForCourse', function () {
        it('returns true when paid', function () {
            $course = Course::factory()->published()->create();

            Payment::factory()->paid()->create([
                'user_id' => $this->learner->id,
                'payable_type' => Course::class,
                'payable_id' => $course->id,
            ]);

            expect($this->paymentService->hasUserPaidForCourse($this->learner, $course))->toBeTrue();
        });

        it('returns false when not paid', function () {
            $course = Course::factory()->published()->create();

            expect($this->paymentService->hasUserPaidForCourse($this->learner, $course))->toBeFalse();
        });

        it('returns false for pending payment', function () {
            $course = Course::factory()->published()->create();

            Payment::factory()->pending()->create([
                'user_id' => $this->learner->id,
                'payable_type' => Course::class,
                'payable_id' => $course->id,
            ]);

            expect($this->paymentService->hasUserPaidForCourse($this->learner, $course))->toBeFalse();
        });
    });

    describe('cancelPayment', function () {
        it('cancels pending payment', function () {
            $payment = Payment::factory()->pending()->create();

            $result = $this->paymentService->cancelPayment($payment, 'User cancelled');

            expect($result->isCancelled())->toBeTrue();
            expect($result->metadata['cancellation_reason'])->toBe('User cancelled');
        });
    });

    describe('refundPayment', function () {
        it('refunds paid payment', function () {
            $payment = Payment::factory()->paid()->create(['amount' => 100000]);

            $result = $this->paymentService->refundPayment(
                $payment,
                $this->admin,
                100000,
                'Customer request'
            );

            expect($result->isRefunded())->toBeTrue();
            expect((float) $result->refund_amount)->toBe(100000.0);
        });

        it('throws error for non-paid payment', function () {
            $payment = Payment::factory()->pending()->create();

            $this->paymentService->refundPayment($payment, $this->admin);
        })->throws(RuntimeException::class, 'cannot be refunded');
    });

    describe('expireStalePayments', function () {
        it('expires old pending payments', function () {
            // Expired payment
            Payment::factory()->pending()->create([
                'expires_at' => now()->subHours(1),
            ]);

            // Valid pending payment
            Payment::factory()->pending()->create([
                'expires_at' => now()->addHours(23),
            ]);

            $count = $this->paymentService->expireStalePayments();

            expect($count)->toBe(1);
            expect(Payment::where('status', Payment::STATUS_EXPIRED)->count())->toBe(1);
        });
    });
});

describe('Enrollment with Payment', function () {
    it('allows enrollment after payment for paid course', function () {
        $course = Course::factory()->published()->create([
            'is_paid' => true,
            'price' => 100000,
        ]);

        // Create paid payment record
        Payment::factory()->paid()->create([
            'user_id' => $this->learner->id,
            'payable_type' => Course::class,
            'payable_id' => $course->id,
        ]);

        // Now enrollment should succeed
        $enrollment = $this->enrollmentService->enroll(
            userId: $this->learner->id,
            courseId: $course->id
        );

        expect($enrollment)->toBeInstanceOf(Enrollment::class);
        expect($enrollment->isActive())->toBeTrue();
    });

    it('blocks enrollment without payment for paid course', function () {
        $course = Course::factory()->published()->create([
            'is_paid' => true,
            'price' => 100000,
        ]);

        $this->enrollmentService->enroll(
            userId: $this->learner->id,
            courseId: $course->id
        );
    })->throws(PaymentRequiredException::class);

    it('blocks enrollment with pending payment', function () {
        $course = Course::factory()->published()->create([
            'is_paid' => true,
            'price' => 100000,
        ]);

        // Create pending (not paid) payment
        Payment::factory()->pending()->create([
            'user_id' => $this->learner->id,
            'payable_type' => Course::class,
            'payable_id' => $course->id,
        ]);

        $this->enrollmentService->enroll(
            userId: $this->learner->id,
            courseId: $course->id
        );
    })->throws(PaymentRequiredException::class);

    it('allows enrollment for free course without payment', function () {
        $course = Course::factory()->published()->create(['is_paid' => false]);

        $enrollment = $this->enrollmentService->enroll(
            userId: $this->learner->id,
            courseId: $course->id
        );

        expect($enrollment)->toBeInstanceOf(Enrollment::class);
    });
});

describe('Payment Model Scopes', function () {
    it('filters by user', function () {
        $otherUser = User::factory()->learner()->create();

        Payment::factory()->count(2)->create(['user_id' => $this->learner->id]);
        Payment::factory()->create(['user_id' => $otherUser->id]);

        expect(Payment::forUser($this->learner)->count())->toBe(2);
    });

    it('filters by course', function () {
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();

        Payment::factory()->count(2)->create([
            'payable_type' => Course::class,
            'payable_id' => $course1->id,
        ]);
        Payment::factory()->create([
            'payable_type' => Course::class,
            'payable_id' => $course2->id,
        ]);

        expect(Payment::forCourse($course1)->count())->toBe(2);
    });

    it('filters by status', function () {
        Payment::factory()->pending()->count(2)->create();
        Payment::factory()->paid()->create();
        Payment::factory()->failed()->create();

        expect(Payment::pending()->count())->toBe(2);
        expect(Payment::paid()->count())->toBe(1);
    });
});
