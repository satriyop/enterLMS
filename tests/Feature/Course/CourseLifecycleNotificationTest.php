<?php

use App\Domain\Course\Events\CourseArchived;
use App\Domain\Course\Events\CourseUnpublished;
use App\Domain\Course\Listeners\LogCourseLifecycleImpact;
use App\Domain\Course\Notifications\CourseAccessChangedNotification;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->admin = User::factory()->create(['role' => 'lms_admin']);
    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->course = Course::factory()->published()->create();
    $this->enrollment = Enrollment::factory()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
        'status' => 'active',
    ]);
});

it('notifies learners when course is unpublished', function () {
    $event = new CourseUnpublished($this->course, $this->admin->id);
    $listener = new LogCourseLifecycleImpact;

    $listener->handle($event);

    Notification::assertSentTo(
        $this->learner,
        CourseAccessChangedNotification::class,
        function ($notification) {
            return $notification->eventType === 'unpublished'
                && $notification->course->id === $this->course->id;
        }
    );
});

it('notifies learners when course is archived', function () {
    $event = new CourseArchived($this->course, $this->admin->id);
    $listener = new LogCourseLifecycleImpact;

    $listener->handle($event);

    Notification::assertSentTo(
        $this->learner,
        CourseAccessChangedNotification::class,
        function ($notification) {
            return $notification->eventType === 'archived'
                && $notification->course->id === $this->course->id;
        }
    );
});

it('does not send notifications when no active enrollments', function () {
    // Drop the enrollment so there are no active enrollments
    $this->enrollment->update(['status' => 'dropped']);

    $event = new CourseUnpublished($this->course, $this->admin->id);
    $listener = new LogCourseLifecycleImpact;

    $listener->handle($event);

    Notification::assertNothingSent();
});

it('notifies multiple learners when course has multiple enrollments', function () {
    $learner2 = User::factory()->create(['role' => 'learner']);
    Enrollment::factory()->create([
        'user_id' => $learner2->id,
        'course_id' => $this->course->id,
        'status' => 'active',
    ]);

    $event = new CourseUnpublished($this->course, $this->admin->id);
    $listener = new LogCourseLifecycleImpact;

    $listener->handle($event);

    Notification::assertSentTo($this->learner, CourseAccessChangedNotification::class);
    Notification::assertSentTo($learner2, CourseAccessChangedNotification::class);
});
