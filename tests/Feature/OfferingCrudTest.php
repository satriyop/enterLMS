<?php

use App\Domain\Shared\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offering;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'lms_admin']);
    $this->course = Course::factory()->published()->create();
});

it('returns 404 when offerings are disabled', function () {
    $this->actingAs($this->admin)
        ->get(route('courses.offerings.index', $this->course))
        ->assertNotFound();
});

it('lists offerings when the capability is on', function () {
    Academy::using('academic');

    $this->actingAs($this->admin)
        ->get(route('courses.offerings.index', $this->course))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/offerings/Index')
            ->where('label', 'Kelas')
            ->has('offerings', 1)
            ->where('offerings.0.is_default', true)
        );
});

it('lists the roster of an offering for lms admin', function () {
    Academy::using('academic');

    $kelasA = Offering::factory()->for($this->course)->create(['name' => 'Kelas A', 'code' => 'a']);
    $learner = User::factory()->learner()->create([
        'name' => 'Budi Santoso',
        'external_id' => '21001001',
    ]);
    Enrollment::factory()->active()->create([
        'user_id' => $learner->id,
        'course_id' => $this->course->id,
        'offering_id' => $kelasA->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('courses.offerings.index', $this->course))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/offerings/Index')
            ->where('offerings.1.name', 'Kelas A')
            ->where('offerings.1.roster.0.external_id', '21001001')
            ->where('offerings.1.roster.0.name', 'Budi Santoso')
        );
});

it('creates a named offering scoped to the course', function () {
    Academy::using('academic');

    $this->actingAs($this->admin)
        ->post(route('courses.offerings.store', $this->course), [
            'name' => 'Kelas A',
            'code' => 'a',
        ])
        ->assertRedirect(route('courses.offerings.index', $this->course));

    expect(Offering::query()->where('course_id', $this->course->id)->where('code', 'a')->exists())->toBeTrue()
        ->and($this->course->offerings()->count())->toBe(2);
});

it('rejects a default code for a named offering', function () {
    Academy::using('academic');

    $this->actingAs($this->admin)
        ->post(route('courses.offerings.store', $this->course), [
            'name' => 'Kelas A',
            'code' => 'default',
        ])
        ->assertSessionHasErrors('code');
});

it('does not update an offering from another course', function () {
    Academy::using('academic');

    $other = Course::factory()->published()->create();
    $foreign = Offering::factory()->for($other)->create(['name' => 'Asli']);

    $this->actingAs($this->admin)
        ->put(route('courses.offerings.update', [$this->course, $foreign]), [
            'name' => 'Diretas',
        ])
        ->assertNotFound();

    expect($foreign->fresh()->name)->toBe('Asli');
});

it('forbids deleting the default offering', function () {
    Academy::using('academic');

    $default = $this->course->ensureDefaultOffering();

    $this->actingAs($this->admin)
        ->delete(route('courses.offerings.destroy', [$this->course, $default]))
        ->assertForbidden();
});

it('forbids a learner from creating an offering', function () {
    Academy::using('academic');

    $learner = User::factory()->create(['role' => 'learner']);

    $this->actingAs($learner)
        ->post(route('courses.offerings.store', $this->course), [
            'name' => 'Kelas A',
        ])
        ->assertForbidden();
});

it('does not delete an offering that still has enrollments', function () {
    Academy::using('academic');

    $offering = Offering::factory()->for($this->course)->create();
    Enrollment::factory()->create([
        'course_id' => $this->course->id,
        'offering_id' => $offering->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('courses.offerings.destroy', [$this->course, $offering]))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Offering::query()->whereKey($offering->id)->exists())->toBeTrue();
});
