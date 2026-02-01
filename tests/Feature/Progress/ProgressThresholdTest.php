<?php

use App\Domain\Progress\DTOs\ProgressUpdateDTO;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;

describe('Media Progress Threshold', function () {
    beforeEach(function () {
        $this->user = User::factory()->create(['role' => 'learner']);
        $this->course = Course::factory()->published()->create();
        $section = CourseSection::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create([
            'course_section_id' => $section->id,
            'content_type' => 'video',
        ]);
        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
        ]);
    });

    it('auto-completes lesson at default 90% media threshold', function () {
        config(['lms.completion_thresholds.media' => 90]);

        $dto = ProgressUpdateDTO::fromArray([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'media_position_seconds' => 90,
            'media_duration_seconds' => 100,
        ]);

        $result = progressService()->updateProgress($dto);

        expect($result->lessonCompleted)->toBeTrue();
    });

    it('does not auto-complete below media threshold', function () {
        config(['lms.completion_thresholds.media' => 90]);

        $dto = ProgressUpdateDTO::fromArray([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'media_position_seconds' => 89,
            'media_duration_seconds' => 100,
        ]);

        $result = progressService()->updateProgress($dto);

        expect($result->lessonCompleted)->toBeFalse();
    });

    it('handles zero media duration without completing', function () {
        $dto = ProgressUpdateDTO::fromArray([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'media_position_seconds' => 0,
            'media_duration_seconds' => 0,
        ]);

        $result = progressService()->updateProgress($dto);

        expect($result->lessonCompleted)->toBeFalse();
    });

    it('auto-completes at custom low threshold', function () {
        config(['lms.completion_thresholds.media' => 50]);

        $dto = ProgressUpdateDTO::fromArray([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'media_position_seconds' => 51,
            'media_duration_seconds' => 100,
        ]);

        $result = progressService()->updateProgress($dto);

        expect($result->lessonCompleted)->toBeTrue();
    });
});

describe('Page Progress Threshold', function () {
    beforeEach(function () {
        $this->user = User::factory()->create(['role' => 'learner']);
        $this->course = Course::factory()->published()->create();
        $section = CourseSection::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create([
            'course_section_id' => $section->id,
            'content_type' => 'text',
        ]);
        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
        ]);
    });

    it('auto-completes at default 100% page threshold', function () {
        config(['lms.completion_thresholds.pages' => 100]);

        $dto = ProgressUpdateDTO::fromArray([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'current_page' => 10,
            'total_pages' => 10,
        ]);

        $result = progressService()->updateProgress($dto);

        expect($result->lessonCompleted)->toBeTrue();
    });

    it('does not auto-complete below page threshold', function () {
        config(['lms.completion_thresholds.pages' => 100]);

        $dto = ProgressUpdateDTO::fromArray([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'current_page' => 9,
            'total_pages' => 10,
        ]);

        $result = progressService()->updateProgress($dto);

        expect($result->lessonCompleted)->toBeFalse();
    });

    it('auto-completes at custom page threshold', function () {
        config(['lms.completion_thresholds.pages' => 80]);

        $dto = ProgressUpdateDTO::fromArray([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'current_page' => 8,
            'total_pages' => 10,
        ]);

        $result = progressService()->updateProgress($dto);

        expect($result->lessonCompleted)->toBeTrue();
    });

    it('handles single page lesson', function () {
        config(['lms.completion_thresholds.pages' => 100]);

        $dto = ProgressUpdateDTO::fromArray([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $this->lesson->id,
            'current_page' => 1,
            'total_pages' => 1,
        ]);

        $result = progressService()->updateProgress($dto);

        expect($result->lessonCompleted)->toBeTrue();
    });
});
