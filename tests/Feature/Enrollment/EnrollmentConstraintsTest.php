<?php

use App\Domain\Enrollment\Exceptions\EnrollmentCapacityExceededException;
use App\Domain\Enrollment\Exceptions\EnrollmentDeadlinePassedException;
use App\Domain\Enrollment\Services\EnrollmentService;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

beforeEach(function () {
    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->course = Course::factory()->published()->create();
    $this->enrollmentService = app(EnrollmentService::class);
});

describe('Enrollment Deadline', function () {
    it('allows enrollment before deadline', function () {
        $this->course->update([
            'enrollment_deadline' => now()->addDays(7),
        ]);

        $enrollment = $this->enrollmentService->enroll(
            userId: $this->learner->id,
            courseId: $this->course->id
        );

        expect($enrollment)->toBeInstanceOf(Enrollment::class);
        expect($enrollment->status->getValue())->toBe('active');
    });

    it('blocks enrollment after deadline', function () {
        $this->course->update([
            'enrollment_deadline' => now()->subDay(),
        ]);

        $this->enrollmentService->enroll(
            userId: $this->learner->id,
            courseId: $this->course->id
        );
    })->throws(EnrollmentDeadlinePassedException::class);

    it('allows enrollment when no deadline is set', function () {
        $this->course->update([
            'enrollment_deadline' => null,
        ]);

        $enrollment = $this->enrollmentService->enroll(
            userId: $this->learner->id,
            courseId: $this->course->id
        );

        expect($enrollment)->toBeInstanceOf(Enrollment::class);
    });

    it('canEnroll returns false after deadline', function () {
        $this->course->update([
            'enrollment_deadline' => now()->subHour(),
        ]);

        expect($this->enrollmentService->canEnroll($this->learner, $this->course))->toBeFalse();
    });
});

describe('Enrollment Capacity', function () {
    it('allows enrollment when capacity not reached', function () {
        $this->course->update([
            'max_enrollments' => 10,
        ]);

        $enrollment = $this->enrollmentService->enroll(
            userId: $this->learner->id,
            courseId: $this->course->id
        );

        expect($enrollment)->toBeInstanceOf(Enrollment::class);
    });

    it('blocks enrollment when at capacity', function () {
        $this->course->update([
            'max_enrollments' => 2,
        ]);

        // Create 2 existing enrollments
        Enrollment::factory()->count(2)->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $this->enrollmentService->enroll(
            userId: $this->learner->id,
            courseId: $this->course->id
        );
    })->throws(EnrollmentCapacityExceededException::class);

    it('allows enrollment when no capacity limit is set', function () {
        $this->course->update([
            'max_enrollments' => null,
        ]);

        $enrollment = $this->enrollmentService->enroll(
            userId: $this->learner->id,
            courseId: $this->course->id
        );

        expect($enrollment)->toBeInstanceOf(Enrollment::class);
    });

    it('counts only active and completed enrollments towards capacity', function () {
        $this->course->update([
            'max_enrollments' => 2,
        ]);

        // Create 1 active and 1 dropped enrollment
        Enrollment::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
        Enrollment::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'dropped',
        ]);

        // Should allow enrollment (only 1 active, dropped doesn't count)
        $enrollment = $this->enrollmentService->enroll(
            userId: $this->learner->id,
            courseId: $this->course->id
        );

        expect($enrollment)->toBeInstanceOf(Enrollment::class);
    });

    it('canEnroll returns false when at capacity', function () {
        $this->course->update([
            'max_enrollments' => 1,
        ]);

        Enrollment::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        expect($this->enrollmentService->canEnroll($this->learner, $this->course))->toBeFalse();
    });
});

describe('Course Helper Methods', function () {
    it('isEnrollmentDeadlinePassed returns correct value', function () {
        // No deadline
        expect($this->course->isEnrollmentDeadlinePassed())->toBeFalse();

        // Future deadline
        $this->course->update(['enrollment_deadline' => now()->addDay()]);
        expect($this->course->isEnrollmentDeadlinePassed())->toBeFalse();

        // Past deadline
        $this->course->update(['enrollment_deadline' => now()->subDay()]);
        expect($this->course->isEnrollmentDeadlinePassed())->toBeTrue();
    });

    it('isAtCapacity returns correct value', function () {
        // No limit
        expect($this->course->isAtCapacity())->toBeFalse();

        // Below limit
        $this->course->update(['max_enrollments' => 10]);
        expect($this->course->isAtCapacity())->toBeFalse();

        // At limit
        Enrollment::factory()->count(10)->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
        expect($this->course->isAtCapacity())->toBeTrue();
    });

    it('getRemainingSpots returns correct value', function () {
        // No limit
        expect($this->course->getRemainingSpots())->toBeNull();

        // With limit and enrollments
        $this->course->update(['max_enrollments' => 10]);
        Enrollment::factory()->count(3)->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
        expect($this->course->getRemainingSpots())->toBe(7);
    });

    it('isEnrollmentOpen checks all conditions', function () {
        // Published, no deadline, no capacity = open
        expect($this->course->isEnrollmentOpen())->toBeTrue();

        // Unpublish = closed
        $this->course->update(['status' => 'draft']);
        expect($this->course->isEnrollmentOpen())->toBeFalse();

        // Republish, past deadline = closed
        $this->course->update([
            'status' => 'published',
            'enrollment_deadline' => now()->subDay(),
        ]);
        expect($this->course->isEnrollmentOpen())->toBeFalse();

        // Future deadline, at capacity = closed
        $this->course->update([
            'enrollment_deadline' => now()->addDay(),
            'max_enrollments' => 1,
        ]);
        Enrollment::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
        expect($this->course->isEnrollmentOpen())->toBeFalse();
    });
});
