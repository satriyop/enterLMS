<?php

namespace Tests\Unit\Policies;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Policies\LessonProgressPolicy;

/**
 * Unit tests for LessonProgressPolicy.
 *
 * Tests verify authorization logic for lesson progress operations.
 * Users must have an active enrollment to update or complete lesson progress.
 *
 * Test Matrix:
 * - Enrollment Status: active, completed, dropped, none
 * - Actions: update, complete
 */
beforeEach(function () {
    $this->policy = new LessonProgressPolicy;

    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->course = Course::factory()->published()->create();
});

// ========== update ==========

it('allows user with active enrollment to update progress', function () {
    Enrollment::factory()->active()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
    ]);

    expect($this->policy->update($this->learner, $this->course))->toBeTrue();
});

it('denies user with completed enrollment to update progress', function () {
    Enrollment::factory()->completed()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
    ]);

    expect($this->policy->update($this->learner, $this->course))->toBeFalse();
});

it('denies user with dropped enrollment to update progress', function () {
    Enrollment::factory()->dropped()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
    ]);

    expect($this->policy->update($this->learner, $this->course))->toBeFalse();
});

it('denies user with no enrollment to update progress', function () {
    expect($this->policy->update($this->learner, $this->course))->toBeFalse();
});

// ========== complete ==========

it('allows user with active enrollment to complete lesson', function () {
    Enrollment::factory()->active()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
    ]);

    expect($this->policy->complete($this->learner, $this->course))->toBeTrue();
});

it('denies user with completed enrollment to complete lesson', function () {
    Enrollment::factory()->completed()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
    ]);

    expect($this->policy->complete($this->learner, $this->course))->toBeFalse();
});

it('denies user with dropped enrollment to complete lesson', function () {
    Enrollment::factory()->dropped()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
    ]);

    expect($this->policy->complete($this->learner, $this->course))->toBeFalse();
});

it('denies user with no enrollment to complete lesson', function () {
    expect($this->policy->complete($this->learner, $this->course))->toBeFalse();
});
