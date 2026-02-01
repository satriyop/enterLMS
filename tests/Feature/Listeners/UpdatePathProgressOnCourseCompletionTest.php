<?php

use App\Domain\Enrollment\Events\EnrollmentCompleted;
use App\Domain\LearningPath\Events\PathProgressUpdated;
use App\Domain\LearningPath\Listeners\UpdatePathProgressOnCourseCompletion;
use App\Domain\LearningPath\States\CompletedCourseState;
use App\Domain\LearningPath\States\InProgressCourseState;
use App\Domain\LearningPath\States\LockedCourseState;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathCourseProgress;
use App\Models\LearningPathEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('UpdatePathProgressOnCourseCompletion', function () {
    it('updates path progress when course enrollment completes', function () {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $learningPath = LearningPath::factory()->published()->create();

        // Attach course to learning path
        $learningPath->courses()->attach($course->id, [
            'position' => 1,
            'is_required' => true,
        ]);

        // Create course enrollment
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        // Create path enrollment
        $pathEnrollment = LearningPathEnrollment::factory()->active()->create([
            'user_id' => $user->id,
            'learning_path_id' => $learningPath->id,
        ]);

        // Create course progress linked to course enrollment
        $courseProgress = LearningPathCourseProgress::factory()->inProgress()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $course->id,
            'course_enrollment_id' => $enrollment->id,
            'position' => 1,
        ]);

        // Create and handle event
        $event = new EnrollmentCompleted($enrollment);
        $listener = app(UpdatePathProgressOnCourseCompletion::class);
        $listener->handle($event);

        // Refresh to get updated state
        $courseProgress->refresh();

        // Assert course progress was marked as completed
        expect($courseProgress->state)->toBeInstanceOf(CompletedCourseState::class)
            ->and($courseProgress->completed_at)->not->toBeNull();

        // Assert path progress percentage was updated
        $pathEnrollment->refresh();
        expect($pathEnrollment->progress_percentage)->toBe(100);
    });

    it('unlocks next courses after completing a course', function () {
        $user = User::factory()->create();
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();
        $learningPath = LearningPath::factory()->published()->create();

        // Attach courses to learning path with sequential order
        $learningPath->courses()->attach($course1->id, [
            'position' => 1,
            'is_required' => true,
            'prerequisites' => null,
        ]);
        $learningPath->courses()->attach($course2->id, [
            'position' => 2,
            'is_required' => true,
            'prerequisites' => json_encode([
                'type' => 'all',
                'courses' => [$course1->id],
            ]),
        ]);

        // Create course enrollment for first course
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course1->id,
        ]);

        // Create path enrollment
        $pathEnrollment = LearningPathEnrollment::factory()->active()->create([
            'user_id' => $user->id,
            'learning_path_id' => $learningPath->id,
        ]);

        // Create course progress for both courses
        $courseProgress1 = LearningPathCourseProgress::factory()->inProgress()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $course1->id,
            'course_enrollment_id' => $enrollment->id,
            'position' => 1,
        ]);

        $courseProgress2 = LearningPathCourseProgress::factory()->locked()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $course2->id,
            'position' => 2,
        ]);

        // Create and handle event
        $event = new EnrollmentCompleted($enrollment);
        $listener = app(UpdatePathProgressOnCourseCompletion::class);
        $listener->handle($event);

        // Refresh course progress records
        $courseProgress1->refresh();
        $courseProgress2->refresh();

        // Assert first course is completed
        expect($courseProgress1->state)->toBeInstanceOf(CompletedCourseState::class);

        // Assert second course is now unlocked (no longer locked)
        expect($courseProgress2->state)->not->toBeInstanceOf(LockedCourseState::class)
            ->and($courseProgress2->unlocked_at)->not->toBeNull()
            ->and($courseProgress2->course_enrollment_id)->not->toBeNull();
    });

    it('completes the learning path when all required courses are done', function () {
        $user = User::factory()->create();
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();
        $learningPath = LearningPath::factory()->published()->create();

        // Attach courses to learning path
        $learningPath->courses()->attach($course1->id, [
            'position' => 1,
            'is_required' => true,
        ]);
        $learningPath->courses()->attach($course2->id, [
            'position' => 2,
            'is_required' => true,
        ]);

        // Create enrollments for both courses (first already completed, second just completed)
        $enrollment1 = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course1->id,
        ]);
        $enrollment2 = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course2->id,
        ]);

        // Create path enrollment
        $pathEnrollment = LearningPathEnrollment::factory()->active()->create([
            'user_id' => $user->id,
            'learning_path_id' => $learningPath->id,
        ]);

        // Create course progress for both courses (first already completed)
        LearningPathCourseProgress::factory()->completed()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $course1->id,
            'course_enrollment_id' => $enrollment1->id,
            'position' => 1,
        ]);

        LearningPathCourseProgress::factory()->inProgress()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $course2->id,
            'course_enrollment_id' => $enrollment2->id,
            'position' => 2,
        ]);

        // Create and handle event for second course completion
        $event = new EnrollmentCompleted($enrollment2);
        $listener = app(UpdatePathProgressOnCourseCompletion::class);
        $listener->handle($event);

        // Refresh path enrollment
        $pathEnrollment->refresh();

        // Assert path is completed
        expect($pathEnrollment->progress_percentage)->toBe(100)
            ->and($pathEnrollment->isCompleted())->toBeTrue()
            ->and($pathEnrollment->completed_at)->not->toBeNull();
    });

    it('does nothing when no path progress records are linked to the enrollment', function () {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();

        // Create course enrollment NOT linked to any learning path
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        // Create and handle event
        $event = new EnrollmentCompleted($enrollment);
        $listener = app(UpdatePathProgressOnCourseCompletion::class);

        // Should not throw any errors
        $listener->handle($event);

        // No assertions needed - just ensuring it doesn't crash
        expect(true)->toBeTrue();
    });

    it('skips inactive (dropped) path enrollments', function () {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $learningPath = LearningPath::factory()->published()->create();

        // Attach course to learning path
        $learningPath->courses()->attach($course->id, [
            'position' => 1,
            'is_required' => true,
        ]);

        // Create course enrollment
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        // Create DROPPED path enrollment
        $pathEnrollment = LearningPathEnrollment::factory()->dropped()->create([
            'user_id' => $user->id,
            'learning_path_id' => $learningPath->id,
        ]);

        // Create course progress
        $courseProgress = LearningPathCourseProgress::factory()->inProgress()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $course->id,
            'course_enrollment_id' => $enrollment->id,
            'position' => 1,
        ]);

        // Create and handle event
        $event = new EnrollmentCompleted($enrollment);
        $listener = app(UpdatePathProgressOnCourseCompletion::class);
        $listener->handle($event);

        // Refresh course progress
        $courseProgress->refresh();

        // Assert course progress was NOT updated (still in progress, not completed)
        expect($courseProgress->state)->toBeInstanceOf(InProgressCourseState::class)
            ->and($courseProgress->completed_at)->toBeNull();

        // Assert path progress was NOT updated
        $pathEnrollment->refresh();
        expect($pathEnrollment->progress_percentage)->toBe(0);
    });

    it('skips already completed path enrollments', function () {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $learningPath = LearningPath::factory()->published()->create();

        // Attach course to learning path
        $learningPath->courses()->attach($course->id, [
            'position' => 1,
            'is_required' => true,
        ]);

        // Create course enrollment
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        // Create COMPLETED path enrollment
        $pathEnrollment = LearningPathEnrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'learning_path_id' => $learningPath->id,
        ]);

        // Create course progress (already completed)
        $courseProgress = LearningPathCourseProgress::factory()->completed()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $course->id,
            'course_enrollment_id' => $enrollment->id,
            'position' => 1,
        ]);

        $originalCompletedAt = $pathEnrollment->completed_at;

        // Create and handle event
        $event = new EnrollmentCompleted($enrollment);
        $listener = app(UpdatePathProgressOnCourseCompletion::class);
        $listener->handle($event);

        // Refresh path enrollment
        $pathEnrollment->refresh();

        // Assert completed_at was not changed
        expect($pathEnrollment->completed_at->toString())->toBe($originalCompletedAt->toString())
            ->and($pathEnrollment->progress_percentage)->toBe(100);
    });

    it('handles multiple path progress records for same course enrollment', function () {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $learningPath1 = LearningPath::factory()->published()->create();
        $learningPath2 = LearningPath::factory()->published()->create();

        // Attach course to both learning paths
        $learningPath1->courses()->attach($course->id, [
            'position' => 1,
            'is_required' => true,
        ]);
        $learningPath2->courses()->attach($course->id, [
            'position' => 1,
            'is_required' => true,
        ]);

        // Create course enrollment (user enrolled in same course via multiple paths)
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        // Create path enrollments for both paths
        $pathEnrollment1 = LearningPathEnrollment::factory()->active()->create([
            'user_id' => $user->id,
            'learning_path_id' => $learningPath1->id,
        ]);
        $pathEnrollment2 = LearningPathEnrollment::factory()->active()->create([
            'user_id' => $user->id,
            'learning_path_id' => $learningPath2->id,
        ]);

        // Create course progress for both paths
        $courseProgress1 = LearningPathCourseProgress::factory()->inProgress()->create([
            'learning_path_enrollment_id' => $pathEnrollment1->id,
            'course_id' => $course->id,
            'course_enrollment_id' => $enrollment->id,
            'position' => 1,
        ]);
        $courseProgress2 = LearningPathCourseProgress::factory()->inProgress()->create([
            'learning_path_enrollment_id' => $pathEnrollment2->id,
            'course_id' => $course->id,
            'course_enrollment_id' => $enrollment->id,
            'position' => 1,
        ]);

        // Create and handle event
        $event = new EnrollmentCompleted($enrollment);
        $listener = app(UpdatePathProgressOnCourseCompletion::class);
        $listener->handle($event);

        // Refresh course progress records
        $courseProgress1->refresh();
        $courseProgress2->refresh();

        // Assert BOTH course progress records were updated
        expect($courseProgress1->state)->toBeInstanceOf(CompletedCourseState::class)
            ->and($courseProgress1->completed_at)->not->toBeNull()
            ->and($courseProgress2->state)->toBeInstanceOf(CompletedCourseState::class)
            ->and($courseProgress2->completed_at)->not->toBeNull();

        // Assert BOTH path enrollments were updated
        $pathEnrollment1->refresh();
        $pathEnrollment2->refresh();

        expect($pathEnrollment1->progress_percentage)->toBe(100)
            ->and($pathEnrollment2->progress_percentage)->toBe(100);
    });

    it('dispatches PathProgressUpdated event when percentage changes', function () {
        Event::fake([PathProgressUpdated::class]);

        $user = User::factory()->create();
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();
        $learningPath = LearningPath::factory()->published()->create();

        // Attach courses to learning path
        $learningPath->courses()->attach($course1->id, [
            'position' => 1,
            'is_required' => true,
        ]);
        $learningPath->courses()->attach($course2->id, [
            'position' => 2,
            'is_required' => true,
        ]);

        // Create course enrollment for first course
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course1->id,
        ]);

        // Create path enrollment
        $pathEnrollment = LearningPathEnrollment::factory()->active()->create([
            'user_id' => $user->id,
            'learning_path_id' => $learningPath->id,
            'progress_percentage' => 0,
        ]);

        // Create course progress for both courses
        LearningPathCourseProgress::factory()->inProgress()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $course1->id,
            'course_enrollment_id' => $enrollment->id,
            'position' => 1,
        ]);

        LearningPathCourseProgress::factory()->locked()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $course2->id,
            'position' => 2,
        ]);

        // Create and handle event
        $event = new EnrollmentCompleted($enrollment);
        $listener = app(UpdatePathProgressOnCourseCompletion::class);
        $listener->handle($event);

        // Assert PathProgressUpdated event was dispatched
        Event::assertDispatched(PathProgressUpdated::class, function ($event) use ($pathEnrollment, $course1) {
            return $event->enrollment->id === $pathEnrollment->id
                && $event->previousPercentage === 0
                && $event->newPercentage === 50
                && $event->completedCourseId === $course1->id;
        });
    });

    it('does not dispatch PathProgressUpdated event when percentage stays the same', function () {
        Event::fake([PathProgressUpdated::class]);

        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $learningPath = LearningPath::factory()->published()->create();

        // Attach course to learning path
        $learningPath->courses()->attach($course->id, [
            'position' => 1,
            'is_required' => true,
        ]);

        // Create course enrollment
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        // Create path enrollment (already at 100%)
        $pathEnrollment = LearningPathEnrollment::factory()->active()->create([
            'user_id' => $user->id,
            'learning_path_id' => $learningPath->id,
            'progress_percentage' => 100,
        ]);

        // Create course progress (already completed)
        LearningPathCourseProgress::factory()->completed()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $course->id,
            'course_enrollment_id' => $enrollment->id,
            'position' => 1,
        ]);

        // Create and handle event
        $event = new EnrollmentCompleted($enrollment);
        $listener = app(UpdatePathProgressOnCourseCompletion::class);
        $listener->handle($event);

        // Assert PathProgressUpdated event was NOT dispatched
        Event::assertNotDispatched(PathProgressUpdated::class);
    });
});
