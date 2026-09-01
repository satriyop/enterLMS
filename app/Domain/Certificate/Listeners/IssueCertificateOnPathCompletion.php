<?php

namespace App\Domain\Certificate\Listeners;

use App\Domain\Certificate\Services\CertificateService;
use App\Domain\LearningPath\Events\PathCompleted;
use Illuminate\Support\Facades\Log;

/**
 * Auto-issue a path diploma when a Learning Path enrollment is completed.
 */
class IssueCertificateOnPathCompletion
{
    public function __construct(
        protected CertificateService $certificateService
    ) {}

    public function handle(PathCompleted $event): void
    {
        $enrollment = $event->enrollment->loadMissing(['user', 'learningPath']);

        try {
            $certificate = $this->certificateService->issuePathCompletionCertificate($enrollment);

            Log::info('Certificate issued on path completion', [
                'certificate_id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'path_enrollment_id' => $enrollment->id,
                'user_id' => $enrollment->user_id,
                'learning_path_id' => $enrollment->learning_path_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to issue certificate on path completion', [
                'path_enrollment_id' => $enrollment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
