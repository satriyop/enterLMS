<?php

use App\Domain\Enrollment\Events\UserEnrolled;
use App\Domain\Enrollment\Listeners\SendWelcomeNotification;
use App\Domain\Enrollment\Notifications\WelcomeToCourseMail;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

describe('SendWelcomeNotification', function () {
    it('sends welcome notification when user enrolls', function () {
        Notification::fake();

        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $event = new UserEnrolled($enrollment, $user->id);
        $listener = new SendWelcomeNotification;
        $listener->handle($event);

        Notification::assertSentTo($user, WelcomeToCourseMail::class);
    });

    it('sends notification to the correct user', function () {
        Notification::fake();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $course = Course::factory()->published()->create();
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $event = new UserEnrolled($enrollment, $user->id);
        $listener = new SendWelcomeNotification;
        $listener->handle($event);

        Notification::assertSentTo($user, WelcomeToCourseMail::class);
        Notification::assertNotSentTo($otherUser, WelcomeToCourseMail::class);
    });

    it('includes correct course information in notification', function () {
        Notification::fake();

        $user = User::factory()->create();
        $course = Course::factory()->published()->create([
            'title' => 'Keamanan Siber untuk Perbankan',
        ]);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $event = new UserEnrolled($enrollment, $user->id);
        $listener = new SendWelcomeNotification;
        $listener->handle($event);

        Notification::assertSentTo(
            $user,
            WelcomeToCourseMail::class,
            function ($notification) use ($course) {
                return $notification->course->id === $course->id
                    && $notification->course->title === 'Keamanan Siber untuk Perbankan';
            }
        );
    });

    it('sends via mail and database channels', function () {
        Notification::fake();

        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $enrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $event = new UserEnrolled($enrollment, $user->id);
        $listener = new SendWelcomeNotification;
        $listener->handle($event);

        Notification::assertSentTo(
            $user,
            WelcomeToCourseMail::class,
            function ($notification) {
                $channels = $notification->via($notification);

                return in_array('mail', $channels) && in_array('database', $channels);
            }
        );
    });
});
