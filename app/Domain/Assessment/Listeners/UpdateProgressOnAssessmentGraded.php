<?php

namespace App\Domain\Assessment\Listeners;

use App\Domain\Assessment\Events\AssessmentGraded;
use App\Domain\Progress\Services\ProgressTrackingService;
use App\Models\Enrollment;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Updates enrollment progress when an assessment is graded.
 *
 * Since progress calculation includes assessment scores (30% weight),
 * we need to recalculate when a grading result changes.
 */
class UpdateProgressOnAssessmentGraded implements ShouldQueue
{
    public string $queue = 'progress';

    public function __construct(
        protected ProgressTrackingService $progressService
    ) {}

    public function handle(AssessmentGraded $event): void
    {
        $attempt = $event->attempt;

        // Load the assessment to get the course_id
        $attempt->loadMissing('assessment');
        $courseId = $attempt->assessment->course_id;

        // Find the user's active enrollment for this course
        $enrollment = Enrollment::query()
            ->where('user_id', $attempt->user_id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if (! $enrollment) {
            return;
        }

        // Recalculate and update progress (may also trigger completion)
        $this->progressService->recalculateCourseProgress($enrollment);
    }
}
