<?php

use App\Domain\LearningPath\Strategies\ImmediatePreviousPrerequisiteEvaluator;
use App\Models\Course;
use App\Models\LearningPath;
use App\Models\LearningPathCourseProgress;
use App\Models\LearningPathEnrollment;

beforeEach(function () {
    $this->evaluator = new ImmediatePreviousPrerequisiteEvaluator;
});

describe('ImmediatePreviousPrerequisiteEvaluator', function () {
    it('returns met for first course', function () {
        $path = LearningPath::factory()->published()->create();
        $course = Course::factory()->published()->create();
        $path->courses()->attach($course->id, ['position' => 1, 'is_required' => true]);

        $enrollment = LearningPathEnrollment::factory()->active()->create([
            'learning_path_id' => $path->id,
        ]);

        $result = $this->evaluator->evaluate($enrollment, $course);

        expect($result->isMet)->toBeTrue();
        expect($result->missingPrerequisites)->toBeEmpty();
    });

    it('returns not met when immediate previous course is incomplete', function () {
        $path = LearningPath::factory()->published()->create();
        $course1 = Course::factory()->published()->create(['title' => 'Course 1']);
        $course2 = Course::factory()->published()->create(['title' => 'Course 2']);

        $path->courses()->attach($course1->id, ['position' => 1, 'is_required' => true]);
        $path->courses()->attach($course2->id, ['position' => 2, 'is_required' => true]);

        $enrollment = LearningPathEnrollment::factory()->active()->create([
            'learning_path_id' => $path->id,
        ]);

        LearningPathCourseProgress::factory()->inProgress()->create([
            'learning_path_enrollment_id' => $enrollment->id,
            'course_id' => $course1->id,
            'position' => 1,
        ]);

        $result = $this->evaluator->evaluate($enrollment, $course2);

        expect($result->isMet)->toBeFalse();
        $titles = array_column($result->missingPrerequisites, 'title');
        expect($titles)->toContain('Course 1');
    });

    it('returns met when immediate previous course is completed', function () {
        $path = LearningPath::factory()->published()->create();
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();

        $path->courses()->attach($course1->id, ['position' => 1, 'is_required' => true]);
        $path->courses()->attach($course2->id, ['position' => 2, 'is_required' => true]);

        $enrollment = LearningPathEnrollment::factory()->active()->create([
            'learning_path_id' => $path->id,
        ]);

        LearningPathCourseProgress::factory()->completed()->create([
            'learning_path_enrollment_id' => $enrollment->id,
            'course_id' => $course1->id,
            'position' => 1,
        ]);

        $result = $this->evaluator->evaluate($enrollment, $course2);

        expect($result->isMet)->toBeTrue();
    });

    it('only checks immediate predecessor, not all previous', function () {
        $path = LearningPath::factory()->published()->create();
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();
        $course3 = Course::factory()->published()->create();

        $path->courses()->attach($course1->id, ['position' => 1, 'is_required' => true]);
        $path->courses()->attach($course2->id, ['position' => 2, 'is_required' => true]);
        $path->courses()->attach($course3->id, ['position' => 3, 'is_required' => true]);

        $enrollment = LearningPathEnrollment::factory()->active()->create([
            'learning_path_id' => $path->id,
        ]);

        // Course 1 is NOT completed, but course 2 IS completed
        LearningPathCourseProgress::factory()->inProgress()->create([
            'learning_path_enrollment_id' => $enrollment->id,
            'course_id' => $course1->id,
            'position' => 1,
        ]);

        LearningPathCourseProgress::factory()->completed()->create([
            'learning_path_enrollment_id' => $enrollment->id,
            'course_id' => $course2->id,
            'position' => 2,
        ]);

        // Course 3 should be met because only course 2 (immediate previous) matters
        $result = $this->evaluator->evaluate($enrollment, $course3);

        expect($result->isMet)->toBeTrue();
    });

    it('returns not met for course not in path', function () {
        $path = LearningPath::factory()->published()->create();
        $courseInPath = Course::factory()->published()->create();
        $courseNotInPath = Course::factory()->published()->create();

        $path->courses()->attach($courseInPath->id, ['position' => 1, 'is_required' => true]);

        $enrollment = LearningPathEnrollment::factory()->active()->create([
            'learning_path_id' => $path->id,
        ]);

        $result = $this->evaluator->evaluate($enrollment, $courseNotInPath);

        expect($result->isMet)->toBeFalse();
        expect($result->reason)->toBe('Course not found in path');
    });

    it('has correct name identifier', function () {
        expect($this->evaluator->getName())->toBe('immediate_previous');
    });
});
