<?php

use App\Domain\Enrollment\Events\EnrollmentCompleted;
use App\Domain\Enrollment\Listeners\SendCompletionCongratulations;
use App\Domain\Enrollment\Notifications\CourseCompletedMail;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

describe('SendCompletionCongratulations', function () {
    it('sends completion notification when enrollment completes', function () {
        Notification::fake();

        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $event = new EnrollmentCompleted($enrollment, $user->id);
        $listener = new SendCompletionCongratulations;
        $listener->handle($event);

        Notification::assertSentTo($user, CourseCompletedMail::class);
    });

    it('sends notification to the correct user', function () {
        Notification::fake();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $course = Course::factory()->published()->create();
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $event = new EnrollmentCompleted($enrollment, $user->id);
        $listener = new SendCompletionCongratulations;
        $listener->handle($event);

        Notification::assertSentTo($user, CourseCompletedMail::class);
        Notification::assertNotSentTo($otherUser, CourseCompletedMail::class);
    });

    it('includes course and enrollment data', function () {
        Notification::fake();

        $user = User::factory()->create();
        $course = Course::factory()->published()->create([
            'title' => 'Manajemen Risiko Keuangan',
        ]);
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $event = new EnrollmentCompleted($enrollment, $user->id);
        $listener = new SendCompletionCongratulations;
        $listener->handle($event);

        Notification::assertSentTo(
            $user,
            CourseCompletedMail::class,
            function ($notification) use ($course, $enrollment) {
                return $notification->course->id === $course->id
                    && $notification->course->title === 'Manajemen Risiko Keuangan'
                    && $notification->enrollment->id === $enrollment->id
                    && $notification->enrollment->user_id === $enrollment->user_id;
            }
        );
    });
});
