<?php

use App\Domain\Course\Exceptions\CannotDeleteDefaultOfferingException;
use App\Domain\Course\Exceptions\OfferingHasEnrollmentsException;
use App\Domain\Shared\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offering;
use App\Models\User;

it('creates a default offering when a course is created', function () {
    $course = Course::factory()->create();

    $offering = $course->defaultOffering;

    expect($offering)->not->toBeNull()
        ->and($offering->code)->toBe(Offering::DEFAULT_CODE)
        ->and($offering->is_default)->toBeTrue()
        ->and($offering->name)->toBe($course->title);
});

it('does not create a second default offering', function () {
    $course = Course::factory()->create();

    $first = $course->ensureDefaultOffering();
    $second = $course->ensureDefaultOffering();

    expect($first->id)->toBe($second->id)
        ->and($course->offerings()->where('is_default', true)->count())->toBe(1);
});

it('is closed before starts_at and after ends_at', function () {
    $course = Course::factory()->create();
    $offering = Offering::factory()->for($course)->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(10),
    ]);

    expect($offering->isOpenForEnrollment())->toBeFalse();

    $this->travel(2)->days();

    expect($offering->fresh()->isOpenForEnrollment())->toBeTrue();

    $this->travel(10)->days();

    expect($offering->fresh()->isOpenForEnrollment())->toBeFalse();
});

it('cannot delete the default offering', function () {
    $course = Course::factory()->create();

    $course->ensureDefaultOffering()->deleteRun();
})->throws(CannotDeleteDefaultOfferingException::class);

it('cannot delete an offering that still has enrollments', function () {
    $course = Course::factory()->published()->create();
    $offering = Offering::factory()->for($course)->create();
    Enrollment::factory()->create([
        'course_id' => $course->id,
        'offering_id' => $offering->id,
    ]);

    $offering->deleteRun();
})->throws(OfferingHasEnrollmentsException::class);

it('assigns a facilitator without introducing a third role', function () {
    $course = Course::factory()->create();
    $facilitator = User::factory()->create(['role' => 'learner']);
    $offering = Offering::factory()->for($course)->create([
        'facilitator_id' => $facilitator->id,
    ]);

    expect($offering->facilitator_id)->toBe($facilitator->id)
        ->and($facilitator->role)->toBe('learner')
        ->and(Academy::preset())->toBe('academy');
});
