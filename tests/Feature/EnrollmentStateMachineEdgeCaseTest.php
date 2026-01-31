<?php

use App\Domain\Enrollment\Events\EnrollmentCompleted;
use App\Domain\Enrollment\Events\UserDropped;
use App\Domain\Enrollment\Events\UserReenrolled;
use App\Domain\Enrollment\Exceptions\AlreadyEnrolledException;
use App\Domain\Enrollment\Services\EnrollmentService;
use App\Domain\Shared\Exceptions\InvalidStateTransitionException;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = app(EnrollmentService::class);
    Event::fake();
});

describe('Enrollment State Machine Edge Cases', function () {

    describe('Enroll when already enrolled in each state', function () {

        it('throws when enrolling active user', function () {
            $user = User::factory()->create();
            $course = Course::factory()->published()->create();
            Enrollment::factory()->active()->create([
                'user_id' => $user->id,
                'course_id' => $course->id,
            ]);

            $this->service->enroll(userId: $user->id, courseId: $course->id);
        })->throws(AlreadyEnrolledException::class);

        it('throws when enrolling completed user', function () {
            $user = User::factory()->create();
            $course = Course::factory()->published()->create();
            Enrollment::factory()->completed()->create([
                'user_id' => $user->id,
                'course_id' => $course->id,
            ]);

            $this->service->enroll(userId: $user->id, courseId: $course->id);
        })->throws(AlreadyEnrolledException::class);

        it('reactivates dropped user via enroll', function () {
            $user = User::factory()->create();
            $course = Course::factory()->published()->create();
            $enrollment = Enrollment::factory()->dropped()->create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'progress_percentage' => 50,
            ]);

            $result = $this->service->enroll(userId: $user->id, courseId: $course->id);

            expect($result->id)->toBe($enrollment->id);
            expect($result->isActive())->toBeTrue();
            // enroll() calls reactivate with preserveProgress: true by default
            expect($result->progress_percentage)->toBe(50);
            Event::assertDispatched(UserReenrolled::class);
        });

    });

    describe('Drop from non-active states', function () {

        it('cannot drop a completed enrollment', function () {
            $enrollment = Enrollment::factory()->completed()->create();

            $enrollment->drop();
        })->throws(InvalidStateTransitionException::class);

        it('cannot drop an already dropped enrollment', function () {
            $enrollment = Enrollment::factory()->dropped()->create();

            $enrollment->drop();
        })->throws(InvalidStateTransitionException::class);

        it('can drop an active enrollment', function () {
            $enrollment = Enrollment::factory()->active()->create();

            $enrollment->drop('Tidak ada waktu');

            expect($enrollment->fresh()->isDropped())->toBeTrue();
            Event::assertDispatched(UserDropped::class);
        });

    });

    describe('Reactivate from non-dropped states', function () {

        it('cannot reactivate an active enrollment', function () {
            $enrollment = Enrollment::factory()->active()->create();

            $enrollment->reactivate();
        })->throws(InvalidStateTransitionException::class);

        it('cannot reactivate a completed enrollment', function () {
            $enrollment = Enrollment::factory()->completed()->create();

            $enrollment->reactivate();
        })->throws(InvalidStateTransitionException::class);

        it('can reactivate a dropped enrollment', function () {
            $enrollment = Enrollment::factory()->dropped()->create([
                'progress_percentage' => 60,
            ]);

            $enrollment->reactivate(preserveProgress: true);

            $fresh = $enrollment->fresh();
            expect($fresh->isActive())->toBeTrue();
            expect($fresh->progress_percentage)->toBe(60);
            Event::assertDispatched(UserReenrolled::class);
        });

        it('reactivation can reset progress', function () {
            $enrollment = Enrollment::factory()->dropped()->create([
                'progress_percentage' => 75,
                'started_at' => now()->subDays(5),
                'last_lesson_id' => null,
            ]);

            $enrollment->reactivate(preserveProgress: false);

            $fresh = $enrollment->fresh();
            expect($fresh->isActive())->toBeTrue();
            expect($fresh->progress_percentage)->toBe(0);
            expect($fresh->started_at)->toBeNull();
            expect($fresh->last_lesson_id)->toBeNull();
        });

    });

    describe('Complete edge cases', function () {

        it('complete is idempotent', function () {
            $enrollment = Enrollment::factory()->completed()->create([
                'completed_at' => now()->subDay(),
            ]);
            $originalCompletedAt = $enrollment->completed_at;

            $enrollment->complete();

            $fresh = $enrollment->fresh();
            expect($fresh->isCompleted())->toBeTrue();
            expect($fresh->completed_at->timestamp)->toBe($originalCompletedAt->timestamp);
            // Should NOT dispatch event again for idempotent call
            Event::assertNotDispatched(EnrollmentCompleted::class);
        });

        it('complete on dropped enrollment transitions to completed (no guard)', function () {
            // Note: complete() only checks isCompleted() for idempotency,
            // it does NOT check isActive(). This means dropped → completed
            // is possible through complete(). If this should be prevented,
            // an isActive() guard should be added to the complete() method.
            $enrollment = Enrollment::factory()->dropped()->create();

            $enrollment->complete();

            expect($enrollment->fresh()->isCompleted())->toBeTrue();
            Event::assertDispatched(EnrollmentCompleted::class);
        });

    });

    describe('HTTP endpoint edge cases', function () {

        it('unenroll returns error when not enrolled', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->create();

            $this->actingAs($learner)
                ->delete(route('courses.unenroll', $course))
                ->assertNotFound();
        });

        it('reenroll returns error when not dropped', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->create();
            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            $response = $this->actingAs($learner)
                ->post(route('courses.reenroll', $course));

            // The app should return an error (redirect with error, 422, etc.)
            expect($response->status())->toBeIn([302, 403, 422]);
        });

        it('reenroll succeeds for dropped enrollment', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->create();
            Enrollment::factory()->dropped()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            $this->actingAs($learner)
                ->post(route('courses.reenroll', $course))
                ->assertRedirect();

            expect(
                Enrollment::where('user_id', $learner->id)
                    ->where('course_id', $course->id)
                    ->first()
                    ->isActive()
            )->toBeTrue();
        });

    });

});
