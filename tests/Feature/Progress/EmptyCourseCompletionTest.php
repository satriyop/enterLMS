<?php

use App\Domain\LearningPath\Services\PathEnrollmentService;
use App\Domain\LearningPath\States\LockedCourseState;
use App\Domain\Progress\Services\ProgressTrackingService;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;

describe('empty Course completion', function () {
    it('does not complete an enrollment or issue a certificate for a course with no content', function () {
        $learner = User::factory()->create(['role' => 'learner']);
        $course = Course::factory()->published()->create();
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        $percentage = app(ProgressTrackingService::class)->recalculateCourseProgress($enrollment);

        $enrollment->refresh();

        expect($percentage)->toBe(0.0);
        expect($enrollment->isCompleted())->toBeFalse();
        expect(Certificate::query()->where('enrollment_id', $enrollment->id)->exists())->toBeFalse();
    });

    it('does not complete an enrollment after every lesson is soft-deleted', function () {
        $learner = User::factory()->create(['role' => 'learner']);
        $course = Course::factory()->published()->create();
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create(['course_section_id' => $section->id]);
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        $progressService = app(ProgressTrackingService::class);
        foreach ($lessons as $lesson) {
            $progressService->completeLesson($enrollment, $lesson);
        }

        $enrollment->refresh();
        expect($enrollment->isCompleted())->toBeTrue();

        foreach ($lessons as $lesson) {
            $lesson->delete();
        }

        $progressService->recalculateCourseProgress($enrollment->fresh());
        $enrollment->refresh();

        expect((float) $enrollment->progress_percentage)->toBe(0.0);
        expect($enrollment->isCompleted())->toBeFalse();
    });

    it('still completes a course that has lessons and no required assessments', function () {
        $learner = User::factory()->create(['role' => 'learner']);
        $course = Course::factory()->published()->create();
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create(['course_section_id' => $section->id]);
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        $progressService = app(ProgressTrackingService::class);
        foreach ($lessons as $lesson) {
            $progressService->completeLesson($enrollment, $lesson);
        }

        $enrollment->refresh();

        expect((float) $enrollment->progress_percentage)->toBe(100.0);
        expect($enrollment->isCompleted())->toBeTrue();
    });

    it('does not unlock the next path course when the current course is empty', function () {
        $learner = User::factory()->create(['role' => 'learner']);
        $empty = Course::factory()->published()->create();
        $next = Course::factory()->published()->create();
        $section = CourseSection::factory()->create(['course_id' => $next->id]);
        Lesson::factory()->create(['course_section_id' => $section->id]);

        $path = LearningPath::factory()->published()->create();
        $path->courses()->attach($empty->id, [
            'position' => 1,
            'is_required' => true,
            'prerequisites' => null,
        ]);
        $path->courses()->attach($next->id, [
            'position' => 2,
            'is_required' => true,
            'prerequisites' => [$empty->id],
        ]);

        $pathEnrollment = app(PathEnrollmentService::class)->enroll($learner, $path);

        $courseEnrollment = Enrollment::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $empty->id)
            ->first();

        expect($courseEnrollment)->not->toBeNull();

        app(ProgressTrackingService::class)->recalculateCourseProgress($courseEnrollment);

        $courseEnrollment->refresh();
        $pathEnrollment->refresh();

        expect($courseEnrollment->isCompleted())->toBeFalse();
        expect(Certificate::query()->where('enrollment_id', $courseEnrollment->id)->exists())->toBeFalse();

        $nextProgress = $pathEnrollment->courseProgress()
            ->where('course_id', $next->id)
            ->first();

        expect($nextProgress)->not->toBeNull();
        expect($nextProgress->isLocked() || $nextProgress->state instanceof LockedCourseState)->toBeTrue();
        expect($nextProgress->course_enrollment_id)->toBeNull();
    });
});
