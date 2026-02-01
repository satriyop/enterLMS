<?php

use App\Domain\LearningPath\Strategies\NoPrerequisiteEvaluator;
use App\Models\Course;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;

beforeEach(function () {
    $this->evaluator = new NoPrerequisiteEvaluator;
});

describe('NoPrerequisiteEvaluator', function () {
    it('returns met for any course', function () {
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

    it('returns met for later courses without prior completion', function () {
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

        // Third course should be available even with no progress
        $result = $this->evaluator->evaluate($enrollment, $course3);

        expect($result->isMet)->toBeTrue();
        expect($result->missingPrerequisites)->toBeEmpty();
    });

    it('has correct name identifier', function () {
        expect($this->evaluator->getName())->toBe('none');
    });
});
