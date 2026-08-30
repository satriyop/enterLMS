<?php

use App\Models\Course;
use App\Models\Offering;
use App\Models\User;
use App\Policies\OfferingPolicy;

beforeEach(function () {
    $this->policy = new OfferingPolicy;
});

it('allows lms admin to manage offerings', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->create();
    $offering = $course->ensureDefaultOffering();

    expect($this->policy->viewAny($admin, $course))->toBeTrue()
        ->and($this->policy->create($admin, $course))->toBeTrue()
        ->and($this->policy->update($admin, $offering))->toBeTrue()
        ->and($this->policy->delete($admin, $offering))->toBeFalse();
});

it('allows a facilitator to view their offering but not create another', function () {
    $facilitator = User::factory()->create(['role' => 'learner']);
    $course = Course::factory()->create();
    $offering = Offering::factory()->for($course)->create([
        'facilitator_id' => $facilitator->id,
    ]);

    expect($this->policy->view($facilitator, $offering))->toBeTrue()
        ->and($this->policy->viewAny($facilitator, $course))->toBeTrue()
        ->and($this->policy->create($facilitator, $course))->toBeFalse()
        ->and($this->policy->update($facilitator, $offering))->toBeFalse()
        ->and($this->policy->grantEnrollment($facilitator, $offering))->toBeTrue();
});

it('denies a learner with no grant', function () {
    $learner = User::factory()->create(['role' => 'learner']);
    $course = Course::factory()->create();
    $offering = $course->ensureDefaultOffering();

    expect($this->policy->viewAny($learner, $course))->toBeFalse()
        ->and($this->policy->view($learner, $offering))->toBeFalse()
        ->and($this->policy->create($learner, $course))->toBeFalse();
});
