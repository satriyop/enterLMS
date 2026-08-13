<?php

namespace Tests\Unit\Policies;

use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\User;
use App\Policies\LearningPathEnrollmentPolicy;

/**
 * Unit tests for LearningPathEnrollmentPolicy.
 *
 * Tests verify authorization logic for learning path enrollment operations.
 * Anyone can view/create enrollments. Only the enrolled user can drop (if active).
 * Only admins can update/delete/restore/forceDelete enrollments.
 */
beforeEach(function () {
    $this->policy = new LearningPathEnrollmentPolicy;

    // System roles: learner, content_manager, trainer, lms_admin
    $this->lmsAdmin = User::factory()->create(['role' => 'lms_admin']);
    $this->trainer = User::factory()->create(['role' => 'lms_admin']);
    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->otherLearner = User::factory()->create(['role' => 'learner']);

    $this->learningPath = LearningPath::factory()->create();
    $this->enrollment = LearningPathEnrollment::factory()->active()->create([
        'user_id' => $this->learner->id,
        'learning_path_id' => $this->learningPath->id,
    ]);
});

// ========== viewAny ==========

it('allows lms_admin to view any enrollments', function () {
    expect($this->policy->viewAny($this->lmsAdmin))->toBeTrue();
});

it('allows learner to view any enrollments', function () {
    expect($this->policy->viewAny($this->learner))->toBeTrue();
});

// ========== view ==========

it('allows learner to view their own enrollment', function () {
    expect($this->policy->view($this->learner, $this->enrollment))->toBeTrue();
});

it('denies other learner to view enrollment', function () {
    expect($this->policy->view($this->otherLearner, $this->enrollment))->toBeFalse();
});

it('allows lms_admin to view any enrollment', function () {
    expect($this->policy->view($this->lmsAdmin, $this->enrollment))->toBeTrue();
});

// ========== create ==========

it('allows learner to create enrollment', function () {
    expect($this->policy->create($this->learner))->toBeTrue();
});

it('allows lms_admin to create enrollment', function () {
    expect($this->policy->create($this->lmsAdmin))->toBeTrue();
});

// ========== drop ==========

it('allows learner to drop their own active enrollment', function () {
    expect($this->policy->drop($this->learner, $this->enrollment))->toBeTrue();
});

it('denies other learner to drop enrollment', function () {
    expect($this->policy->drop($this->otherLearner, $this->enrollment))->toBeFalse();
});

it('denies dropping completed enrollment', function () {
    // Create separate learning path to avoid unique constraint violation
    $separatePath = LearningPath::factory()->create();
    $completedEnrollment = LearningPathEnrollment::factory()->completed()->create([
        'user_id' => $this->learner->id,
        'learning_path_id' => $separatePath->id,
    ]);

    expect($this->policy->drop($this->learner, $completedEnrollment))->toBeFalse();
});

it('denies dropping already dropped enrollment', function () {
    // Create separate learning path to avoid unique constraint violation
    $separatePath = LearningPath::factory()->create();
    $droppedEnrollment = LearningPathEnrollment::factory()->dropped()->create([
        'user_id' => $this->learner->id,
        'learning_path_id' => $separatePath->id,
    ]);

    expect($this->policy->drop($this->learner, $droppedEnrollment))->toBeFalse();
});

it('denies lms_admin to drop other users enrollment', function () {
    // lms_admin cannot drop via this method - they must use update/delete
    expect($this->policy->drop($this->lmsAdmin, $this->enrollment))->toBeFalse();
});

// ========== update ==========

it('allows lms_admin to update enrollment', function () {
    expect($this->policy->update($this->lmsAdmin, $this->enrollment))->toBeTrue();
});

it('denies learner to update their own enrollment', function () {
    expect($this->policy->update($this->learner, $this->enrollment))->toBeFalse();
});

// ========== delete ==========

it('allows lms_admin to delete enrollment', function () {
    expect($this->policy->delete($this->lmsAdmin, $this->enrollment))->toBeTrue();
});

it('denies learner to delete their own enrollment', function () {
    expect($this->policy->delete($this->learner, $this->enrollment))->toBeFalse();
});

// ========== restore ==========

it('allows lms_admin to restore enrollment', function () {
    expect($this->policy->restore($this->lmsAdmin, $this->enrollment))->toBeTrue();
});

it('denies learner to restore enrollment', function () {
    expect($this->policy->restore($this->learner, $this->enrollment))->toBeFalse();
});

// ========== forceDelete ==========

it('allows lms_admin to force delete enrollment', function () {
    expect($this->policy->forceDelete($this->lmsAdmin, $this->enrollment))->toBeTrue();
});

it('denies learner to force delete enrollment', function () {
    expect($this->policy->forceDelete($this->learner, $this->enrollment))->toBeFalse();
});
