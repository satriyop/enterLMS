<?php

use App\Domain\Enrollment\Exceptions\AlreadyEnrolledException;
use App\Domain\Enrollment\Exceptions\OfferingClosedForEnrollmentException;
use App\Domain\Enrollment\Services\EnrollmentService;
use App\Domain\Shared\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offering;
use App\Models\User;

beforeEach(function () {
    $this->service = app(EnrollmentService::class);
    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->course = Course::factory()->published()->public()->create();
});

it('attaches the default offering when enrolling without an offering id', function () {
    $enrollment = $this->service->enroll(
        userId: $this->learner->id,
        courseId: $this->course->id,
    );

    expect($enrollment->offering_id)->toBe($this->course->ensureDefaultOffering()->id);
});

it('enrolls into a named offering when the capability is on', function () {
    Academy::using('academic');

    $kelasA = Offering::factory()->for($this->course)->create(['name' => 'Kelas A']);

    $this->actingAs($this->learner)
        ->post(route('courses.enroll', $this->course), [
            'offering_id' => $kelasA->id,
        ])
        ->assertRedirect();

    expect(Enrollment::query()
        ->where('user_id', $this->learner->id)
        ->where('offering_id', $kelasA->id)
        ->exists())->toBeTrue();
});

it('ignores offering_id when offerings are disabled', function () {
    $kelasA = Offering::factory()->for($this->course)->create(['name' => 'Kelas A']);

    $this->actingAs($this->learner)
        ->post(route('courses.enroll', $this->course), [
            'offering_id' => $kelasA->id,
        ])
        ->assertRedirect();

    $enrollment = Enrollment::query()->where('user_id', $this->learner->id)->first();

    expect($enrollment->offering_id)->toBe($this->course->ensureDefaultOffering()->id);
});

it('allows a later offering after the first enrollment is completed', function () {
    $first = $this->course->ensureDefaultOffering();
    $second = Offering::factory()->for($this->course)->create(['name' => 'Kelas B']);

    $enrollment = $this->service->enroll(
        userId: $this->learner->id,
        courseId: $this->course->id,
        offeringId: $first->id,
    );
    $enrollment->complete();

    $next = $this->service->enroll(
        userId: $this->learner->id,
        courseId: $this->course->id,
        offeringId: $second->id,
    );

    expect($next->offering_id)->toBe($second->id)
        ->and($next->id)->not->toBe($enrollment->id);
});

it('blocks a second active enrollment on the same course', function () {
    $this->service->enroll(
        userId: $this->learner->id,
        courseId: $this->course->id,
    );

    $second = Offering::factory()->for($this->course)->create();

    $this->service->enroll(
        userId: $this->learner->id,
        courseId: $this->course->id,
        offeringId: $second->id,
    );
})->throws(AlreadyEnrolledException::class);

it('rejects enrollment on a closed offering', function () {
    $offering = Offering::factory()->for($this->course)->create([
        'starts_at' => now()->addWeek(),
    ]);

    $this->service->enroll(
        userId: $this->learner->id,
        courseId: $this->course->id,
        offeringId: $offering->id,
    );
})->throws(OfferingClosedForEnrollmentException::class);

it('rejects an offering that does not belong to the course', function () {
    $other = Course::factory()->published()->create();
    $foreign = Offering::factory()->for($other)->create();

    $this->service->enroll(
        userId: $this->learner->id,
        courseId: $this->course->id,
        offeringId: $foreign->id,
    );
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('lets lms admin grant a learner onto a named offering of a restricted course', function () {
    Academy::using('academic');

    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->published()->restricted()->create();
    $kelasA = Offering::factory()->for($course)->create(['name' => 'Kelas A', 'code' => 'a']);

    $this->actingAs($admin)
        ->post(route('courses.bulk-enroll', $course), [
            'user_ids' => [$this->learner->id],
            'offering_id' => $kelasA->id,
        ])
        ->assertRedirect(route('courses.show', $course));

    $enrollment = Enrollment::query()
        ->where('user_id', $this->learner->id)
        ->where('course_id', $course->id)
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->offering_id)->toBe($kelasA->id)
        ->and($enrollment->invited_by)->toBe($admin->id);
});

it('rejects a grant onto the default offering when named offerings exist', function () {
    Academy::using('academic');

    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->published()->restricted()->create();
    Offering::factory()->for($course)->create(['name' => 'Kelas A', 'code' => 'a']);
    $default = $course->ensureDefaultOffering();

    $this->actingAs($admin)
        ->post(route('courses.bulk-enroll', $course), [
            'user_ids' => [$this->learner->id],
            'offering_id' => $default->id,
        ])
        ->assertRedirect(route('courses.show', $course))
        ->assertSessionHas('error');

    expect(session('error'))->toContain('default')
        ->and(Enrollment::query()->where('user_id', $this->learner->id)->where('course_id', $course->id)->exists())->toBeFalse();
});

it('grants onto the default offering when it is the only offering', function () {
    Academy::using('academic');

    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->published()->restricted()->create();
    $default = $course->ensureDefaultOffering();

    $this->actingAs($admin)
        ->post(route('courses.bulk-enroll', $course), [
            'user_ids' => [$this->learner->id],
            'offering_id' => $default->id,
        ])
        ->assertRedirect(route('courses.show', $course));

    $enrollment = Enrollment::query()
        ->where('user_id', $this->learner->id)
        ->where('course_id', $course->id)
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->offering_id)->toBe($default->id)
        ->and($enrollment->invited_by)->toBe($admin->id);
});

it('does not attach the default offering when self-enrolling without an offering id and named offerings exist', function () {
    Academy::using('academic');

    Offering::factory()->for($this->course)->create(['name' => 'Kelas A', 'code' => 'a']);

    $this->actingAs($this->learner)
        ->post(route('courses.enroll', $this->course))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(session('error'))->toContain('default')
        ->and(Enrollment::query()->where('user_id', $this->learner->id)->where('course_id', $this->course->id)->exists())->toBeFalse();
});

it('keeps restricted self-enroll forbidden', function () {
    Academy::using('academic');

    $course = Course::factory()->published()->restricted()->create();
    $kelasA = Offering::factory()->for($course)->create(['name' => 'Kelas A', 'code' => 'a']);

    $this->actingAs($this->learner)
        ->post(route('courses.enroll', $course), [
            'offering_id' => $kelasA->id,
        ])
        ->assertForbidden();
});

it('allows a later offering after the first enrollment is dropped', function () {
    $first = $this->course->ensureDefaultOffering();
    $second = Offering::factory()->for($this->course)->create(['name' => 'Kelas B']);

    $enrollment = $this->service->enroll(
        userId: $this->learner->id,
        courseId: $this->course->id,
        offeringId: $first->id,
    );
    $enrollment->drop();

    $next = $this->service->enroll(
        userId: $this->learner->id,
        courseId: $this->course->id,
        offeringId: $second->id,
    );

    expect($next->offering_id)->toBe($second->id)
        ->and($next->id)->not->toBe($enrollment->id);
});
