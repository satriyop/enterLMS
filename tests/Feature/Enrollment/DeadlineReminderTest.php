<?php

use App\Domain\Enrollment\Notifications\EnrollmentDeadlineReminderNotification;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->otherLearner = User::factory()->create(['role' => 'learner']);

    // Give the other learner an enrollment so they qualify as "active learner"
    $otherCourse = Course::factory()->published()->create();
    Enrollment::factory()->create([
        'user_id' => $this->otherLearner->id,
        'course_id' => $otherCourse->id,
        'status' => 'active',
    ]);
});

describe('SendDeadlineReminders Command', function () {
    it('sends reminder for course with deadline in 3 days', function () {
        $course = Course::factory()->published()->create([
            'enrollment_deadline' => now()->addDays(3),
        ]);

        $this->artisan('app:send-deadline-reminders', ['--days' => '3'])
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->otherLearner,
            EnrollmentDeadlineReminderNotification::class
        );
    });

    it('sends reminder for course with deadline in 1 day', function () {
        $course = Course::factory()->published()->create([
            'enrollment_deadline' => now()->addDays(1),
        ]);

        $this->artisan('app:send-deadline-reminders', ['--days' => '1'])
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->otherLearner,
            EnrollmentDeadlineReminderNotification::class
        );
    });

    it('does not send reminder for course with deadline in wrong day', function () {
        $course = Course::factory()->published()->create([
            'enrollment_deadline' => now()->addDays(5), // Not in 3 or 1 days
        ]);

        $this->artisan('app:send-deadline-reminders', ['--days' => '3,1'])
            ->assertSuccessful();

        Notification::assertNothingSent();
    });

    it('does not send reminder to already enrolled learners', function () {
        $course = Course::factory()->published()->create([
            'enrollment_deadline' => now()->addDays(3),
        ]);

        // Enroll the other learner in this course
        Enrollment::factory()->create([
            'user_id' => $this->otherLearner->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->artisan('app:send-deadline-reminders', ['--days' => '3'])
            ->assertSuccessful();

        Notification::assertNotSentTo($this->otherLearner, EnrollmentDeadlineReminderNotification::class);
    });

    it('does not send reminder for unpublished courses', function () {
        $course = Course::factory()->create([
            'status' => 'draft',
            'enrollment_deadline' => now()->addDays(3),
        ]);

        $this->artisan('app:send-deadline-reminders', ['--days' => '3'])
            ->assertSuccessful();

        Notification::assertNothingSent();
    });

    it('respects dry-run option', function () {
        $course = Course::factory()->published()->create([
            'enrollment_deadline' => now()->addDays(3),
        ]);

        $this->artisan('app:send-deadline-reminders', ['--days' => '3', '--dry-run' => true])
            ->assertSuccessful();

        Notification::assertNothingSent();
    });

    it('handles multiple days configuration', function () {
        $course3Days = Course::factory()->published()->create([
            'enrollment_deadline' => now()->addDays(3),
        ]);
        $course1Day = Course::factory()->published()->create([
            'enrollment_deadline' => now()->addDays(1),
        ]);

        $this->artisan('app:send-deadline-reminders', ['--days' => '3,1'])
            ->assertSuccessful();

        // Should send 2 notifications (one for each course)
        Notification::assertSentToTimes($this->otherLearner, EnrollmentDeadlineReminderNotification::class, 2);
    });
});

describe('EnrollmentDeadlineReminderNotification', function () {
    it('sends via mail and database channels', function () {
        $course = Course::factory()->published()->create([
            'enrollment_deadline' => now()->addDays(3),
        ]);

        $notification = new EnrollmentDeadlineReminderNotification(
            $course,
            $course->enrollment_deadline,
            3
        );

        expect($notification->via($this->learner))->toBe(['mail', 'database']);
    });

    it('includes course info in database notification', function () {
        $course = Course::factory()->published()->create([
            'title' => 'Test Course',
            'enrollment_deadline' => now()->addDays(3),
        ]);

        $notification = new EnrollmentDeadlineReminderNotification(
            $course,
            $course->enrollment_deadline,
            3
        );

        $data = $notification->toArray($this->learner);

        expect($data)->toHaveKey('course_id', $course->id);
        expect($data)->toHaveKey('course_title', 'Test Course');
        expect($data)->toHaveKey('days_remaining', 3);
        expect($data['type'])->toBe('enrollment.deadline_reminder');
    });

    it('shows urgency for 1 day remaining', function () {
        $course = Course::factory()->published()->create([
            'title' => 'Urgent Course',
            'enrollment_deadline' => now()->addDay(),
        ]);

        $notification = new EnrollmentDeadlineReminderNotification(
            $course,
            $course->enrollment_deadline,
            1
        );

        $mail = $notification->toMail($this->learner);

        expect($mail->subject)->toContain('Mendesak');
    });
});
