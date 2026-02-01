<?php

use App\Domain\LearningPath\Events\PathEnrollmentCreated;
use App\Domain\LearningPath\Listeners\SendPathEnrollmentWelcome;
use App\Domain\LearningPath\Notifications\PathEnrollmentWelcomeMail;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

describe('SendPathEnrollmentWelcome', function () {
    it('sends welcome notification when user enrolls in learning path', function () {
        Notification::fake();

        $user = User::factory()->create();
        $path = LearningPath::factory()->published()->create();
        $enrollment = LearningPathEnrollment::factory()->create([
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
        ]);

        $event = new PathEnrollmentCreated($enrollment, $user->id);
        $listener = new SendPathEnrollmentWelcome;
        $listener->handle($event);

        Notification::assertSentTo($user, PathEnrollmentWelcomeMail::class);
    });

    it('sends notification to the correct user', function () {
        Notification::fake();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $path = LearningPath::factory()->published()->create();
        $enrollment = LearningPathEnrollment::factory()->create([
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
        ]);

        $event = new PathEnrollmentCreated($enrollment, $user->id);
        $listener = new SendPathEnrollmentWelcome;
        $listener->handle($event);

        Notification::assertSentTo($user, PathEnrollmentWelcomeMail::class);
        Notification::assertNotSentTo($otherUser, PathEnrollmentWelcomeMail::class);
    });

    it('includes learning path data', function () {
        Notification::fake();

        $user = User::factory()->create();
        $path = LearningPath::factory()->published()->create([
            'title' => 'Program Transformasi Digital Perbankan',
        ]);
        $enrollment = LearningPathEnrollment::factory()->create([
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
        ]);

        $event = new PathEnrollmentCreated($enrollment, $user->id);
        $listener = new SendPathEnrollmentWelcome;
        $listener->handle($event);

        Notification::assertSentTo(
            $user,
            PathEnrollmentWelcomeMail::class,
            function ($notification) use ($path, $enrollment) {
                return $notification->path->id === $path->id
                    && $notification->path->title === 'Program Transformasi Digital Perbankan'
                    && $notification->enrollment->id === $enrollment->id;
            }
        );
    });
});
