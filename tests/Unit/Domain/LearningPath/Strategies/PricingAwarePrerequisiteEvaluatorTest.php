<?php

use App\Domain\LearningPath\Strategies\PricingAwarePrerequisiteEvaluator;
use App\Models\Course;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;

beforeEach(function () {
    $this->evaluator = new PricingAwarePrerequisiteEvaluator;
});

describe('PricingAwarePrerequisiteEvaluator', function () {
    it('returns met in internal mode regardless of course pricing', function () {
        config(['lms.mode' => 'internal']);

        $path = LearningPath::factory()->published()->create();
        $course = Course::factory()->published()->create(['is_paid' => true]);
        $path->courses()->attach($course->id, ['position' => 1, 'is_required' => true]);

        $enrollment = LearningPathEnrollment::factory()->active()->create([
            'learning_path_id' => $path->id,
        ]);

        $result = $this->evaluator->evaluate($enrollment, $course);

        expect($result->isMet)->toBeTrue();
    });

    it('returns not met for paid courses in commercial mode', function () {
        config(['lms.mode' => 'commercial']);

        $path = LearningPath::factory()->published()->create();
        $course = Course::factory()->published()->create(['is_paid' => true]);
        $path->courses()->attach($course->id, ['position' => 1, 'is_required' => true]);

        $enrollment = LearningPathEnrollment::factory()->active()->create([
            'learning_path_id' => $path->id,
        ]);

        $result = $this->evaluator->evaluate($enrollment, $course);

        expect($result->isMet)->toBeFalse();
        expect($result->reason)->toContain('Pembayaran diperlukan');
    });

    it('returns met for free courses in commercial mode', function () {
        config(['lms.mode' => 'commercial']);

        $path = LearningPath::factory()->published()->create();
        $course = Course::factory()->published()->create(['is_paid' => false]);
        $path->courses()->attach($course->id, ['position' => 1, 'is_required' => true]);

        $enrollment = LearningPathEnrollment::factory()->active()->create([
            'learning_path_id' => $path->id,
        ]);

        $result = $this->evaluator->evaluate($enrollment, $course);

        expect($result->isMet)->toBeTrue();
    });

    it('has correct name identifier', function () {
        expect($this->evaluator->getName())->toBe('pricing_aware');
    });
});
