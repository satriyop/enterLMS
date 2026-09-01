<?php

use App\Domain\Certificate\Services\CertificateService;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\User;

beforeEach(function () {
    $this->learner = User::factory()->learner()->create();
    $this->admin = User::factory()->lmsAdmin()->create();
    $this->course = Course::factory()->published()->create();
    $this->service = app(CertificateService::class);
});

describe('Certificate Model', function () {
    it('generates unique certificate numbers', function () {
        // Create a certificate to increment the count
        Certificate::factory()->create();
        $number1 = Certificate::generateCertificateNumber();

        // Create another certificate
        Certificate::factory()->create();
        $number2 = Certificate::generateCertificateNumber();

        expect($number1)->toStartWith('CERT-');
        expect($number2)->toStartWith('CERT-');
        // Numbers are based on count so they will differ after creating certificates
        expect($number1)->not->toBe($number2);
    });

    it('generates unique verification codes', function () {
        $code1 = Certificate::generateVerificationCode();
        $code2 = Certificate::generateVerificationCode();

        expect(strlen($code1))->toBe(16);
        expect($code1)->not->toBe($code2);
    });

    it('checks active status', function () {
        $certificate = Certificate::factory()->active()->make();
        expect($certificate->isActive())->toBeTrue();
        expect($certificate->isRevoked())->toBeFalse();
    });

    it('checks revoked status', function () {
        $certificate = Certificate::factory()->revoked()->make();
        expect($certificate->isRevoked())->toBeTrue();
        expect($certificate->isActive())->toBeFalse();
    });

    it('checks expired status by date', function () {
        $certificate = Certificate::factory()
            ->active()
            ->withExpiration(-30) // Expired 30 days ago
            ->make();

        expect($certificate->isExpired())->toBeTrue();
    });

    it('can be revoked', function () {
        $certificate = Certificate::factory()->active()->create();

        $certificate->revoke($this->admin, 'Test revocation');

        expect($certificate->fresh()->isRevoked())->toBeTrue();
        expect($certificate->fresh()->revoked_by)->toBe($this->admin->id);
        expect($certificate->fresh()->revocation_reason)->toBe('Test revocation');
    });
});

describe('CertificateService', function () {
    describe('issueCourseCompletionCertificate', function () {
        it('issues certificate for completed enrollment', function () {
            $enrollment = Enrollment::factory()->completed()->create([
                'user_id' => $this->learner->id,
                'course_id' => $this->course->id,
                'progress_percentage' => 100,
            ]);

            $certificate = $this->service->issueCourseCompletionCertificate($enrollment);

            expect($certificate)->toBeInstanceOf(Certificate::class);
            expect($certificate->user_id)->toBe($this->learner->id);
            expect($certificate->certificable_type)->toBe(Course::class);
            expect($certificate->certificable_id)->toBe($this->course->id);
            expect($certificate->enrollment_id)->toBe($enrollment->id);
            expect($certificate->recipient_name)->toBe($this->learner->name);
            expect($certificate->certificable_title)->toBe($this->course->title);
            expect($certificate->status)->toBe(Certificate::STATUS_ACTIVE);
        });

        it('returns existing certificate if already issued', function () {
            $enrollment = Enrollment::factory()->completed()->create([
                'user_id' => $this->learner->id,
                'course_id' => $this->course->id,
            ]);

            $first = $this->service->issueCourseCompletionCertificate($enrollment);
            $second = $this->service->issueCourseCompletionCertificate($enrollment);

            expect($first->id)->toBe($second->id);
        });

        it('records issued_by when provided', function () {
            $enrollment = Enrollment::factory()->completed()->create([
                'user_id' => $this->learner->id,
                'course_id' => $this->course->id,
            ]);

            $certificate = $this->service->issueCourseCompletionCertificate(
                $enrollment,
                $this->admin
            );

            expect($certificate->issued_by)->toBe($this->admin->id);
        });
    });

    describe('issuePathCompletionCertificate', function () {
        it('issues certificate for a completed path enrollment', function () {
            $pathEnrollment = LearningPathEnrollment::factory()->completed()->create([
                'user_id' => $this->learner->id,
            ]);

            $certificate = $this->service->issuePathCompletionCertificate($pathEnrollment);

            expect($certificate)->toBeInstanceOf(Certificate::class);
            expect($certificate->user_id)->toBe($this->learner->id);
            expect($certificate->type)->toBe(Certificate::TYPE_LEARNING_PATH_COMPLETION);
            expect($certificate->certificable_type)->toBe(LearningPath::class);
            expect($certificate->certificable_id)->toBe($pathEnrollment->learning_path_id);
            expect($certificate->enrollment_id)->toBeNull();
            expect($certificate->recipient_name)->toBe($this->learner->name);
            expect($certificate->certificable_title)->toBe($pathEnrollment->learningPath->title);
            expect($certificate->status)->toBe(Certificate::STATUS_ACTIVE);
        });

        it('returns existing path certificate if already issued', function () {
            $pathEnrollment = LearningPathEnrollment::factory()->completed()->create([
                'user_id' => $this->learner->id,
            ]);

            $first = $this->service->issuePathCompletionCertificate($pathEnrollment);
            $second = $this->service->issuePathCompletionCertificate($pathEnrollment);

            expect($first->id)->toBe($second->id);
        });

        it('records issued_by when provided', function () {
            $pathEnrollment = LearningPathEnrollment::factory()->completed()->create([
                'user_id' => $this->learner->id,
            ]);

            $certificate = $this->service->issuePathCompletionCertificate(
                $pathEnrollment,
                $this->admin
            );

            expect($certificate->issued_by)->toBe($this->admin->id);
        });
    });

    describe('verify', function () {
        it('returns valid for active certificate', function () {
            $certificate = Certificate::factory()->active()->create();

            $result = $this->service->verify($certificate->verification_code);

            expect($result['valid'])->toBeTrue();
            expect($result['certificate']->id)->toBe($certificate->id);
            expect($result['message'])->toContain('valid');
        });

        it('returns invalid for non-existent code', function () {
            $result = $this->service->verify('NONEXISTENT123456');

            expect($result['valid'])->toBeFalse();
            expect($result['certificate'])->toBeNull();
            expect($result['message'])->toContain('tidak ditemukan');
        });

        it('returns invalid for revoked certificate', function () {
            $certificate = Certificate::factory()->revoked()->create();

            $result = $this->service->verify($certificate->verification_code);

            expect($result['valid'])->toBeFalse();
            expect($result['message'])->toContain('dicabut');
        });

        it('returns invalid for expired certificate', function () {
            $certificate = Certificate::factory()
                ->expired()
                ->create();

            $result = $this->service->verify($certificate->verification_code);

            expect($result['valid'])->toBeFalse();
            expect($result['message'])->toContain('kedaluwarsa');
        });
    });

    describe('getUserCertificates', function () {
        it('returns only active certificates for user', function () {
            Certificate::factory()->active()->create([
                'user_id' => $this->learner->id,
            ]);
            Certificate::factory()->active()->create([
                'user_id' => $this->learner->id,
            ]);
            Certificate::factory()->revoked()->create([
                'user_id' => $this->learner->id,
            ]);

            $certificates = $this->service->getUserCertificates($this->learner);

            expect($certificates)->toHaveCount(2);
        });
    });

    describe('hasCertificateForCourse', function () {
        it('returns true when certificate exists', function () {
            Certificate::factory()->active()->create([
                'user_id' => $this->learner->id,
                'certificable_type' => Course::class,
                'certificable_id' => $this->course->id,
            ]);

            expect($this->service->hasCertificateForCourse($this->learner, $this->course))
                ->toBeTrue();
        });

        it('returns false when no certificate exists', function () {
            expect($this->service->hasCertificateForCourse($this->learner, $this->course))
                ->toBeFalse();
        });
    });
});

describe('CertificateController', function () {
    it('allows learner to download own certificate', function () {
        $certificate = Certificate::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $response = $this->actingAs($this->learner)
            ->get(route('certificates.download', $certificate));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    });

    it('denies downloading other user certificate', function () {
        $otherLearner = User::factory()->learner()->create();
        $certificate = Certificate::factory()->active()->create([
            'user_id' => $otherLearner->id,
        ]);

        $response = $this->actingAs($this->learner)
            ->get(route('certificates.download', $certificate));

        $response->assertForbidden();
    });

    it('allows admin to download any certificate', function () {
        $certificate = Certificate::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('certificates.download', $certificate));

        $response->assertOk();
    });

    it('denies downloading revoked certificate', function () {
        $certificate = Certificate::factory()->revoked()->create([
            'user_id' => $this->learner->id,
        ]);

        $response = $this->actingAs($this->learner)
            ->get(route('certificates.download', $certificate));

        $response->assertForbidden();
    });

    it('verifies certificate publicly', function () {
        $certificate = Certificate::factory()->active()->create();

        $response = $this->get(route('certificates.verify', $certificate->verification_code));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('certificates/VerifyResult', shouldExist: false)
            ->where('valid', true)
            ->has('certificate')
        );
    });

    it('handles invalid verification code', function () {
        $response = $this->get(route('certificates.verify', 'INVALIDCODE123'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('valid', false)
            ->where('certificate', null)
        );
    });
});

describe('Auto-issue on completion', function () {
    it('issues certificate when enrollment is completed', function () {
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
            'course_id' => $this->course->id,
        ]);

        // Manually complete the enrollment
        $enrollment->complete();

        // Check that certificate was issued
        $certificate = Certificate::where('enrollment_id', $enrollment->id)->first();

        expect($certificate)->not->toBeNull();
        expect($certificate->user_id)->toBe($this->learner->id);
        expect($certificate->certificable_id)->toBe($this->course->id);
    });

    it('issues a path diploma when path enrollment is completed', function () {
        $pathEnrollment = LearningPathEnrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $pathEnrollment->complete();

        $certificate = Certificate::query()
            ->where('user_id', $this->learner->id)
            ->where('type', Certificate::TYPE_LEARNING_PATH_COMPLETION)
            ->first();

        expect($certificate)->not->toBeNull();
        expect($certificate->certificable_type)->toBe(LearningPath::class);
        expect($certificate->certificable_id)->toBe($pathEnrollment->learning_path_id);
        expect($certificate->enrollment_id)->toBeNull();
    });
});

describe('Certificate PDF', function () {
    it('prints the named verification url on the template', function () {
        $certificate = Certificate::factory()->active()->create();
        $url = $certificate->getVerificationUrl();

        expect($url)->toBe(route('certificates.verify', $certificate->verification_code));

        $method = new \ReflectionMethod(CertificateService::class, 'prepareCertificateData');
        $data = $method->invoke($this->service, $certificate);

        expect($data['verification_url'])->toBe($url);

        $view = $this->view('pdf.certificate', $data);

        $view->assertSee($url, false);
        $view->assertDontSee(config('app.url').'/verify', false);
    });

    it('uses path copy on the template for a path diploma', function () {
        $certificate = Certificate::factory()->learningPathCompletion()->active()->create();

        $method = new \ReflectionMethod(CertificateService::class, 'prepareCertificateData');
        $data = $method->invoke($this->service, $certificate);

        $view = $this->view('pdf.certificate', $data);

        $view->assertSee('Penyelesaian Learning Path', false);
        $view->assertSee('Telah berhasil menyelesaikan learning path', false);
        $view->assertDontSee('Penyelesaian Kursus', false);
    });
});

describe('Certificate Policy', function () {
    it('allows owner to view certificate', function () {
        $certificate = Certificate::factory()->create([
            'user_id' => $this->learner->id,
        ]);

        expect($this->learner->can('view', $certificate))->toBeTrue();
    });

    it('denies other learner from viewing', function () {
        $otherLearner = User::factory()->learner()->create();
        $certificate = Certificate::factory()->create([
            'user_id' => $this->learner->id,
        ]);

        expect($otherLearner->can('view', $certificate))->toBeFalse();
    });

    it('allows admin to view any certificate', function () {
        $certificate = Certificate::factory()->create([
            'user_id' => $this->learner->id,
        ]);

        expect($this->admin->can('view', $certificate))->toBeTrue();
    });

    it('only allows admin to revoke', function () {
        $certificate = Certificate::factory()->create();

        expect($this->learner->can('revoke', $certificate))->toBeFalse();
        expect($this->admin->can('revoke', $certificate))->toBeTrue();
    });
});
