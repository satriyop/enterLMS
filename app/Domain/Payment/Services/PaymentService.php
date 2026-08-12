<?php

namespace App\Domain\Payment\Services;

use App\Domain\Enrollment\Services\EnrollmentService;
use App\Domain\Payment\Contracts\PaymentGatewayContract;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment orchestration service.
 *
 * Handles payment creation, processing, and enrollment linking.
 * Gateway-specific logic is delegated to PaymentGatewayContract implementations.
 */
class PaymentService
{
    protected ?PaymentGatewayContract $gateway;

    public function __construct(
        protected EnrollmentService $enrollmentService,
        ?PaymentGatewayContract $gateway = null,
    ) {
        $this->gateway = $gateway ?? (
            app()->bound(PaymentGatewayContract::class)
                ? app(PaymentGatewayContract::class)
                : null
        );
    }

    /**
     * Create a payment for a course.
     */
    public function createCoursePayment(User $user, Course $course): Payment
    {
        $this->ensurePaymentsEnabled();
        $this->ensureGatewayConfigured();
        $this->validateCoursePayment($user, $course);

        return $this->createPayment(
            user: $user,
            payable: $course,
            amount: $course->price,
            currency: $course->currency
        );
    }

    /**
     * Create a payment for a learning path.
     */
    public function createLearningPathPayment(User $user, LearningPath $learningPath): Payment
    {
        $this->ensurePaymentsEnabled();
        $this->ensureGatewayConfigured();

        // Learning paths may have aggregate pricing
        $price = $learningPath->price ?? $this->calculateLearningPathPrice($learningPath);

        return $this->createPayment(
            user: $user,
            payable: $learningPath,
            amount: $price,
            currency: $learningPath->currency ?? 'IDR'
        );
    }

    /**
     * Payments are product-disabled until commercial mode + flag + gateway exist.
     */
    protected function ensurePaymentsEnabled(): void
    {
        if (config('lms.mode') !== 'commercial' || ! config('lms.payment.enabled')) {
            throw new \RuntimeException(
                'Pembayaran dinonaktifkan. LMS beroperasi dalam mode gratis/internal.'
            );
        }
    }

    /**
     * Hard-fail paid flows when no PaymentGatewayContract is bound.
     */
    protected function ensureGatewayConfigured(): void
    {
        if ($this->gateway === null) {
            throw new \RuntimeException(
                'Gateway pembayaran belum dikonfigurasi. Kursus berbayar tidak dapat diproses.'
            );
        }
    }

    /**
     * Create a payment record.
     *
     * @param  Course|LearningPath  $payable
     */
    protected function createPayment(
        User $user,
        Model $payable,
        float $amount,
        string $currency = 'IDR'
    ): Payment {
        return DB::transaction(function () use ($user, $payable, $amount, $currency) {
            $payment = Payment::create([
                'payable_type' => get_class($payable),
                'payable_id' => $payable->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => Payment::STATUS_PENDING,
                'gateway' => config('lms.payment.gateway'),
                'invoice_number' => Payment::generateInvoiceNumber(),
                'expires_at' => now()->addHours(24), // Default 24-hour expiration
            ]);

            Log::info('Payment created', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'payable_type' => get_class($payable),
                'payable_id' => $payable->id,
                'amount' => $amount,
            ]);

            return $payment;
        });
    }

    /**
     * Initiate payment with gateway.
     *
     * Returns payment with gateway transaction details (URL, transaction ID).
     */
    public function initiatePayment(Payment $payment): Payment
    {
        $this->ensurePaymentsEnabled();
        $this->ensureGatewayConfigured();

        if (! $payment->canProcess()) {
            throw new \RuntimeException('Pembayaran tidak dapat diproses pada status saat ini.');
        }

        $result = $this->gateway->createTransaction($payment);

        $payment->markProcessing(
            gatewayTransactionId: $result['transaction_id'],
            paymentUrl: $result['payment_url']
        );

        if ($result['expires_at']) {
            $payment->update(['expires_at' => $result['expires_at']]);
        }

        return $payment->fresh();
    }

    /**
     * Handle successful payment (from webhook or callback).
     *
     * Creates enrollment after payment is confirmed.
     */
    public function handlePaymentSuccess(
        Payment $payment,
        ?string $gatewayTransactionId = null,
        ?string $paymentType = null,
        ?array $metadata = null
    ): Payment {
        if (! $payment->canMarkPaid()) {
            Log::warning('Attempted to mark non-pending payment as paid', [
                'payment_id' => $payment->id,
                'current_status' => $payment->status,
            ]);

            return $payment;
        }

        return DB::transaction(function () use ($payment, $gatewayTransactionId, $paymentType, $metadata) {
            $payment->markPaid($gatewayTransactionId, $paymentType, $metadata);

            // Create enrollment after successful payment
            $enrollment = $this->createEnrollmentForPayment($payment);

            if ($enrollment) {
                $payment->update(['enrollment_id' => $enrollment->id]);
            }

            Log::info('Payment completed and enrollment created', [
                'payment_id' => $payment->id,
                'enrollment_id' => $enrollment?->id,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Handle payment failure.
     */
    public function handlePaymentFailure(Payment $payment, ?string $reason = null): Payment
    {
        $payment->markFailed($reason);

        Log::info('Payment failed', [
            'payment_id' => $payment->id,
            'reason' => $reason,
        ]);

        return $payment;
    }

    /**
     * Handle payment expiration.
     */
    public function handlePaymentExpiration(Payment $payment): Payment
    {
        if ($payment->isPending() || $payment->isProcessing()) {
            $payment->markExpired();

            Log::info('Payment expired', ['payment_id' => $payment->id]);
        }

        return $payment;
    }

    /**
     * Cancel a pending payment.
     */
    public function cancelPayment(Payment $payment, ?string $reason = null): Payment
    {
        if ($this->gateway && $payment->gateway_transaction_id) {
            $this->gateway->cancel($payment);
        }

        $payment->markCancelled($reason);

        Log::info('Payment cancelled', [
            'payment_id' => $payment->id,
            'reason' => $reason,
        ]);

        return $payment;
    }

    /**
     * Process refund for a paid payment.
     */
    public function refundPayment(
        Payment $payment,
        User $refundedBy,
        ?float $amount = null,
        string $reason = 'Customer request'
    ): Payment {
        if (! $payment->canRefund()) {
            throw new \RuntimeException('Payment cannot be refunded in current state');
        }

        $refundAmount = $amount ?? $payment->amount;

        if ($this->gateway && $payment->gateway_transaction_id) {
            $this->gateway->refund($payment, $refundAmount, $reason);
        }

        $payment->refund($refundedBy, $refundAmount, $reason);

        Log::info('Payment refunded', [
            'payment_id' => $payment->id,
            'refund_amount' => $refundAmount,
            'refunded_by' => $refundedBy->id,
        ]);

        return $payment;
    }

    /**
     * Check if user has paid for a course.
     */
    public function hasUserPaidForCourse(User $user, Course $course): bool
    {
        return Payment::query()
            ->forUser($user)
            ->forCourse($course)
            ->paid()
            ->exists();
    }

    /**
     * Get user's payment for a course.
     */
    public function getUserPaymentForCourse(User $user, Course $course): ?Payment
    {
        return Payment::query()
            ->forUser($user)
            ->forCourse($course)
            ->latest()
            ->first();
    }

    /**
     * Get pending payment for user and course.
     */
    public function getPendingPayment(User $user, Course $course): ?Payment
    {
        return Payment::query()
            ->forUser($user)
            ->forCourse($course)
            ->where(function ($query) {
                $query->pending()->orWhere('status', Payment::STATUS_PROCESSING);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Expire all stale pending payments.
     *
     * Should be run via scheduler.
     */
    public function expireStalePayments(): int
    {
        $expired = Payment::query()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $payment) {
            $this->handlePaymentExpiration($payment);
        }

        return $expired->count();
    }

    /**
     * Create enrollment after successful payment.
     */
    protected function createEnrollmentForPayment(Payment $payment): ?Enrollment
    {
        if ($payment->payable_type !== Course::class) {
            // Handle learning path enrollment separately if needed
            return null;
        }

        try {
            return $this->enrollmentService->enroll(
                userId: $payment->user_id,
                courseId: $payment->payable_id
            );
        } catch (\Throwable $e) {
            Log::error('Failed to create enrollment after payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Validate that a course payment can be created.
     */
    protected function validateCoursePayment(User $user, Course $course): void
    {
        if (! $course->isPaid()) {
            throw new \RuntimeException('Course is not a paid course');
        }

        if ($this->hasUserPaidForCourse($user, $course)) {
            throw new \RuntimeException('User has already paid for this course');
        }

        // Check for existing pending payment
        $pendingPayment = $this->getPendingPayment($user, $course);
        if ($pendingPayment) {
            throw new \RuntimeException('User already has a pending payment for this course');
        }

        // Check if already enrolled
        $activeEnrollment = $this->enrollmentService->getActiveEnrollment($user, $course);
        if ($activeEnrollment) {
            throw new \RuntimeException('User is already enrolled in this course');
        }
    }

    /**
     * Calculate total price for a learning path.
     */
    protected function calculateLearningPathPrice(LearningPath $learningPath): float
    {
        // Sum prices of all paid courses in the path
        // Could apply discount based on bundle pricing
        return $learningPath->courses()
            ->where('is_paid', true)
            ->sum('price');
    }
}
