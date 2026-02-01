<?php

use App\Domain\LearningPath\Events\PathCompleted;
use App\Domain\LearningPath\Listeners\SendPathCompletionCongratulations;
use App\Domain\LearningPath\Notifications\PathCompletedMail;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

describe('SendPathCompletionCongratulations', function () {
    it('sends completion notification when path completes', function () {
        Notification::fake();

        $user = User::factory()->create();
        $path = LearningPath::factory()->published()->create();
        $enrollment = LearningPathEnrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
        ]);

        $completedCourses = 5;
        $event = new PathCompleted($enrollment, $completedCourses, $user->id);
        $listener = new SendPathCompletionCongratulations;
        $listener->handle($event);

        Notification::assertSentTo($user, PathCompletedMail::class);
    });

    it('sends notification to the correct user', function () {
        Notification::fake();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $path = LearningPath::factory()->published()->create();
        $enrollment = LearningPathEnrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
        ]);

        $completedCourses = 3;
        $event = new PathCompleted($enrollment, $completedCourses, $user->id);
        $listener = new SendPathCompletionCongratulations;
        $listener->handle($event);

        Notification::assertSentTo($user, PathCompletedMail::class);
        Notification::assertNotSentTo($otherUser, PathCompletedMail::class);
    });

    it('includes completed courses count', function () {
        Notification::fake();

        $user = User::factory()->create();
        $path = LearningPath::factory()->published()->create([
            'title' => 'Program Kepatuhan Anti Pencucian Uang',
        ]);
        $enrollment = LearningPathEnrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
        ]);

        $completedCourses = 7;
        $event = new PathCompleted($enrollment, $completedCourses, $user->id);
        $listener = new SendPathCompletionCongratulations;
        $listener->handle($event);

        Notification::assertSentTo(
            $user,
            PathCompletedMail::class,
            function ($notification) use ($path, $enrollment, $completedCourses) {
                return $notification->path->id === $path->id
                    && $notification->path->title === 'Program Kepatuhan Anti Pencucian Uang'
                    && $notification->enrollment->id === $enrollment->id
                    && $notification->completedCourses === $completedCourses;
            }
        );
    });
});
