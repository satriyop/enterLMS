<?php

use App\Domain\LearningPath\Strategies\ImmediatePreviousPrerequisiteEvaluator;
use App\Domain\LearningPath\Strategies\SequentialPrerequisiteEvaluator;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathCourseProgress;
use App\Models\LearningPathEnrollment;
use App\Models\User;

beforeEach(function () {
    $this->learner = User::factory()->create(['role' => 'learner']);

    // Create learning path with 2 courses
    $this->course1 = Course::factory()->published()->create();
    $this->course2 = Course::factory()->published()->create();

    $this->path = LearningPath::factory()->published()->create([
        'prerequisite_mode' => 'sequential',
    ]);
});

describe('SequentialPrerequisiteEvaluator', function () {
    it('allows access when previous course meets min_completion_percentage', function () {
        // Set min_completion_percentage to 80%
        $this->path->courses()->attach($this->course1->id, [
            'position' => 1,
            'is_required' => true,
            'min_completion_percentage' => 80,
        ]);
        $this->path->courses()->attach($this->course2->id, [
            'position' => 2,
            'is_required' => true,
        ]);

        // Create path enrollment
        $pathEnrollment = LearningPathEnrollment::factory()->create([
            'user_id' => $this->learner->id,
            'learning_path_id' => $this->path->id,
        ]);

        // Create course enrollment with 85% progress (above threshold)
        $courseEnrollment = Enrollment::factory()->create([
            'user_id' => $this->learner->id,
            'course_id' => $this->course1->id,
            'status' => 'active', // Not completed
            'progress_percentage' => 85,
        ]);

        // Create progress record for course 1 (in_progress state)
        LearningPathCourseProgress::factory()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $this->course1->id,
            'course_enrollment_id' => $courseEnrollment->id,
            'state' => 'in_progress', // NOT completed
            'position' => 1,
        ]);

        $evaluator = new SequentialPrerequisiteEvaluator;
        $result = $evaluator->evaluate($pathEnrollment, $this->course2);

        expect($result->isMet)->toBeTrue();
    });

    it('blocks access when previous course below min_completion_percentage', function () {
        // Set min_completion_percentage to 80%
        $this->path->courses()->attach($this->course1->id, [
            'position' => 1,
            'is_required' => true,
            'min_completion_percentage' => 80,
        ]);
        $this->path->courses()->attach($this->course2->id, [
            'position' => 2,
            'is_required' => true,
        ]);

        $pathEnrollment = LearningPathEnrollment::factory()->create([
            'user_id' => $this->learner->id,
            'learning_path_id' => $this->path->id,
        ]);

        // Create course enrollment with only 75% progress (below threshold)
        $courseEnrollment = Enrollment::factory()->create([
            'user_id' => $this->learner->id,
            'course_id' => $this->course1->id,
            'status' => 'active',
            'progress_percentage' => 75,
        ]);

        LearningPathCourseProgress::factory()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $this->course1->id,
            'course_enrollment_id' => $courseEnrollment->id,
            'state' => 'in_progress',
            'position' => 1,
        ]);

        $evaluator = new SequentialPrerequisiteEvaluator;
        $result = $evaluator->evaluate($pathEnrollment, $this->course2);

        expect($result->isMet)->toBeFalse();
        expect($result->missingPrerequisites)->toHaveCount(1);
    });

    it('requires full completion when min_completion_percentage is null', function () {
        // No min_completion_percentage set
        $this->path->courses()->attach($this->course1->id, [
            'position' => 1,
            'is_required' => true,
            'min_completion_percentage' => null,
        ]);
        $this->path->courses()->attach($this->course2->id, [
            'position' => 2,
            'is_required' => true,
        ]);

        $pathEnrollment = LearningPathEnrollment::factory()->create([
            'user_id' => $this->learner->id,
            'learning_path_id' => $this->path->id,
        ]);

        // Course has 95% progress but is NOT completed
        $courseEnrollment = Enrollment::factory()->create([
            'user_id' => $this->learner->id,
            'course_id' => $this->course1->id,
            'status' => 'active',
            'progress_percentage' => 95,
        ]);

        LearningPathCourseProgress::factory()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $this->course1->id,
            'course_enrollment_id' => $courseEnrollment->id,
            'state' => 'in_progress',
            'position' => 1,
        ]);

        $evaluator = new SequentialPrerequisiteEvaluator;
        $result = $evaluator->evaluate($pathEnrollment, $this->course2);

        // Should NOT meet prerequisite because min is 100% (default)
        expect($result->isMet)->toBeFalse();
    });

    it('allows access when course is fully completed regardless of threshold', function () {
        $this->path->courses()->attach($this->course1->id, [
            'position' => 1,
            'is_required' => true,
            'min_completion_percentage' => 80,
        ]);
        $this->path->courses()->attach($this->course2->id, [
            'position' => 2,
            'is_required' => true,
        ]);

        $pathEnrollment = LearningPathEnrollment::factory()->create([
            'user_id' => $this->learner->id,
            'learning_path_id' => $this->path->id,
        ]);

        $courseEnrollment = Enrollment::factory()->create([
            'user_id' => $this->learner->id,
            'course_id' => $this->course1->id,
            'status' => 'completed',
            'progress_percentage' => 100,
        ]);

        // Completed state
        LearningPathCourseProgress::factory()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $this->course1->id,
            'course_enrollment_id' => $courseEnrollment->id,
            'state' => 'completed',
            'position' => 1,
        ]);

        $evaluator = new SequentialPrerequisiteEvaluator;
        $result = $evaluator->evaluate($pathEnrollment, $this->course2);

        expect($result->isMet)->toBeTrue();
    });
});

describe('ImmediatePreviousPrerequisiteEvaluator', function () {
    it('allows access when previous course meets min_completion_percentage', function () {
        $this->path->courses()->attach($this->course1->id, [
            'position' => 1,
            'is_required' => true,
            'min_completion_percentage' => 70,
        ]);
        $this->path->courses()->attach($this->course2->id, [
            'position' => 2,
            'is_required' => true,
        ]);

        $pathEnrollment = LearningPathEnrollment::factory()->create([
            'user_id' => $this->learner->id,
            'learning_path_id' => $this->path->id,
        ]);

        $courseEnrollment = Enrollment::factory()->create([
            'user_id' => $this->learner->id,
            'course_id' => $this->course1->id,
            'status' => 'active',
            'progress_percentage' => 72,
        ]);

        LearningPathCourseProgress::factory()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $this->course1->id,
            'course_enrollment_id' => $courseEnrollment->id,
            'state' => 'in_progress',
            'position' => 1,
        ]);

        $evaluator = new ImmediatePreviousPrerequisiteEvaluator;
        $result = $evaluator->evaluate($pathEnrollment, $this->course2);

        expect($result->isMet)->toBeTrue();
    });

    it('blocks access when below min_completion_percentage', function () {
        $this->path->courses()->attach($this->course1->id, [
            'position' => 1,
            'is_required' => true,
            'min_completion_percentage' => 70,
        ]);
        $this->path->courses()->attach($this->course2->id, [
            'position' => 2,
            'is_required' => true,
        ]);

        $pathEnrollment = LearningPathEnrollment::factory()->create([
            'user_id' => $this->learner->id,
            'learning_path_id' => $this->path->id,
        ]);

        $courseEnrollment = Enrollment::factory()->create([
            'user_id' => $this->learner->id,
            'course_id' => $this->course1->id,
            'status' => 'active',
            'progress_percentage' => 65, // Below 70%
        ]);

        LearningPathCourseProgress::factory()->create([
            'learning_path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $this->course1->id,
            'course_enrollment_id' => $courseEnrollment->id,
            'state' => 'in_progress',
            'position' => 1,
        ]);

        $evaluator = new ImmediatePreviousPrerequisiteEvaluator;
        $result = $evaluator->evaluate($pathEnrollment, $this->course2);

        expect($result->isMet)->toBeFalse();
    });
});
