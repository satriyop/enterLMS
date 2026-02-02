<?php

namespace App\Domain\Course\Listeners;

use App\Domain\Course\Events\CourseArchived;
use App\Domain\Course\Events\CourseUnpublished;
use App\Models\Enrollment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Logs the impact of course lifecycle changes (unpublish/archive)
 * on active enrollments.
 *
 * Future: Send notifications to affected learners.
 */
class LogCourseLifecycleImpact implements ShouldQueue
{
    public string $queue = 'audit';

    public function handle(CourseUnpublished|CourseArchived $event): void
    {
        $course = $event->course;

        $activeEnrollmentCount = Enrollment::query()
            ->where('course_id', $course->id)
            ->active()
            ->count();

        if ($activeEnrollmentCount === 0) {
            return;
        }

        Log::warning('Course lifecycle change affects active enrollments', [
            'event' => $event->getEventName(),
            'course_id' => $course->id,
            'course_title' => $course->title,
            'active_enrollments_affected' => $activeEnrollmentCount,
            'actor_id' => $event->actorId,
        ]);

        // TODO: Send notifications to affected learners when notification system is ready
        // Enrollment::query()
        //     ->where('course_id', $course->id)
        //     ->active()
        //     ->with('user')
        //     ->each(function (Enrollment $enrollment) use ($course, $event) {
        //         $enrollment->user->notify(new CourseStatusChangedNotification($course, $event->getEventName()));
        //     });
    }
}
