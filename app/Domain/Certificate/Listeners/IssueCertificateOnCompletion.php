<?php

namespace App\Domain\Certificate\Listeners;

use App\Domain\Certificate\Services\CertificateService;
use App\Domain\Enrollment\Events\EnrollmentCompleted;
use Illuminate\Support\Facades\Log;

/**
 * Auto-issue certificate when enrollment is completed.
 */
class IssueCertificateOnCompletion
{
    public function __construct(
        protected CertificateService $certificateService
    ) {}

    public function handle(EnrollmentCompleted $event): void
    {
        $enrollment = $event->enrollment;

        // Only issue certificate for course completions (not path enrollments)
        if ($enrollment->course_id === null) {
            return;
        }

        try {
            $certificate = $this->certificateService->issueCourseCompletionCertificate($enrollment);

            Log::info('Certificate issued on enrollment completion', [
                'certificate_id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'enrollment_id' => $enrollment->id,
                'user_id' => $enrollment->user_id,
                'course_id' => $enrollment->course_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to issue certificate on enrollment completion', [
                'enrollment_id' => $enrollment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
