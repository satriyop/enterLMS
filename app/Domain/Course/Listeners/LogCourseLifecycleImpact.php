<?php

namespace App\Domain\Course\Listeners;

use App\Domain\Course\Events\CourseArchived;
use App\Domain\Course\Events\CourseUnpublished;
use App\Domain\Course\Notifications\CourseAccessChangedNotification;
use App\Models\Enrollment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Logs the impact of course lifecycle changes (unpublish/archive)
 * on active enrollments and notifies affected learners.
 */
class LogCourseLifecycleImpact implements ShouldQueue
{
    public string $queue = 'audit';

    public function handle(CourseUnpublished|CourseArchived $event): void
    {
        $course = $event->course;

        $activeEnrollments = Enrollment::query()
            ->where('course_id', $course->id)
            ->active()
            ->with('user')
            ->get();

        if ($activeEnrollments->isEmpty()) {
            return;
        }

        Log::warning('Course lifecycle change affects active enrollments', [
            'event' => $event->getEventName(),
            'course_id' => $course->id,
            'course_title' => $course->title,
            'active_enrollments_affected' => $activeEnrollments->count(),
            'actor_id' => $event->actorId,
        ]);

        // Determine event type for notification message
        $eventType = $event instanceof CourseUnpublished ? 'unpublished' : 'archived';

        // Notify all affected learners
        foreach ($activeEnrollments as $enrollment) {
            $enrollment->user->notify(
                new CourseAccessChangedNotification($course, $eventType)
            );
        }
    }
}
