<?php

use App\Domain\Assessment\Events\AssessmentGraded;
use App\Domain\Assessment\Listeners\UpdateProgressOnAssessmentGraded;
use App\Domain\Progress\Services\ProgressTrackingService;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

beforeEach(function () {
    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->course = Course::factory()->published()->create();
    $this->enrollment = Enrollment::factory()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
    $this->assessment = Assessment::factory()->published()->create([
        'course_id' => $this->course->id,
        'is_required' => true,
    ]);
});

it('updates enrollment progress when assessment is graded', function () {
    // Create a passed attempt
    $attempt = AssessmentAttempt::factory()->passed()->create([
        'assessment_id' => $this->assessment->id,
        'user_id' => $this->learner->id,
    ]);

    // Get the progress service
    $progressService = app(ProgressTrackingService::class);

    // Create and invoke the listener
    $listener = new UpdateProgressOnAssessmentGraded($progressService);
    $event = new AssessmentGraded($attempt);

    $listener->handle($event);

    // Progress should have been updated
    $this->enrollment->refresh();

    // With 0 lessons and 1 passed required assessment:
    // Lesson progress = 100% (no lessons)
    // Assessment progress = 100% (1/1 passed)
    // Total = (100 * 0.7) + (100 * 0.3) = 100
    expect((float) $this->enrollment->progress_percentage)->toBe(100.0);
});

it('does not crash when no enrollment exists', function () {
    // Create attempt for user without enrollment in this course
    $otherUser = User::factory()->create();
    $attempt = AssessmentAttempt::factory()->passed()->create([
        'assessment_id' => $this->assessment->id,
        'user_id' => $otherUser->id,
    ]);

    $progressService = app(ProgressTrackingService::class);
    $listener = new UpdateProgressOnAssessmentGraded($progressService);
    $event = new AssessmentGraded($attempt);

    // Should not throw
    $listener->handle($event);

    expect(true)->toBeTrue();
});

it('triggers enrollment completion when all requirements met', function () {
    // Create a passed attempt
    $attempt = AssessmentAttempt::factory()->passed()->create([
        'assessment_id' => $this->assessment->id,
        'user_id' => $this->learner->id,
    ]);

    $progressService = app(ProgressTrackingService::class);
    $listener = new UpdateProgressOnAssessmentGraded($progressService);
    $event = new AssessmentGraded($attempt);

    $listener->handle($event);

    $this->enrollment->refresh();

    // With no lessons and assessment passed, enrollment should complete
    expect($this->enrollment->isCompleted())->toBeTrue();
});
