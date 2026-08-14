<?php

namespace App\Domain\Progress\Listeners;

use App\Domain\Enrollment\States\ActiveState;
use App\Domain\Enrollment\States\CompletedState;
use App\Domain\Progress\Events\LessonDeleted;
use App\Domain\Progress\Services\ProgressTrackingService;
use App\Models\Enrollment;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecalculateProgressOnLessonDeletion implements ShouldQueue
{
    public function __construct(
        protected ProgressTrackingService $progressService,
    ) {}

    public function handle(LessonDeleted $event): void
    {
        $course = $event->course;
        $course->loadCount('lessons');

        $enrollments = Enrollment::query()
            ->where('course_id', $course->id)
            ->whereIn('status', [ActiveState::$name, CompletedState::$name])
            ->get();

        if ($enrollments->isEmpty()) {
            return;
        }

        foreach ($enrollments as $enrollment) {
            // Share the already-loaded course to avoid N identical course queries
            $enrollment->setRelation('course', $course);
            $this->progressService->recalculateCourseProgress($enrollment);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['progress', 'lesson-deletion'];
    }
}
