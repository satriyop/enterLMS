<?php

use App\Domain\Shared\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offering;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('does not send a facilitator with no enrollments to the browse carousel', function () {
    Academy::using('academic');

    $facilitator = User::factory()->create(['role' => 'learner']);
    $course = Course::factory()->published()->restricted()->create();
    $kelasA = Offering::factory()->for($course)->create([
        'name' => 'Kelas A',
        'code' => 'a',
        'facilitator_id' => $facilitator->id,
    ]);
    Offering::factory()->for($course)->create(['name' => 'Kelas B', 'code' => 'b']);
    Course::factory()->published()->public()->create();

    $this->actingAs($facilitator)
        ->get(route('learner.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('learner/Dashboard')
            ->where('browseCourses', [])
            ->where('featuredCourses', [])
            ->has('facilitatedOfferings', 1)
            ->where('facilitatedOfferings.0.id', $kelasA->id)
            ->where('facilitatedOfferings.0.name', 'Kelas A')
            ->where('myLearning', [])
        );
});

it('lists only offerings a facilitator is granted, never another kelas', function () {
    Academy::using('academic');

    $facilitator = User::factory()->create(['role' => 'learner']);
    $course = Course::factory()->published()->create();
    Offering::factory()->for($course)->create([
        'name' => 'Kelas A',
        'code' => 'a',
        'facilitator_id' => $facilitator->id,
    ]);
    Offering::factory()->for($course)->create(['name' => 'Kelas B', 'code' => 'b']);

    $this->actingAs($facilitator)
        ->get(route('learner.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('learner/Dashboard')
            ->has('facilitatedOfferings', 1)
            ->where('facilitatedOfferings.0.name', 'Kelas A')
        );
});

it('shows a learner their granted kelas as home, not the public catalog', function () {
    Academy::using('academic');

    $learner = User::factory()->create(['role' => 'learner']);
    $course = Course::factory()->published()->restricted()->create();
    $kelasA = Offering::factory()->for($course)->create(['name' => 'Kelas A', 'code' => 'a']);
    Course::factory()->published()->public()->create();

    Enrollment::factory()->active()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'offering_id' => $kelasA->id,
    ]);

    $this->actingAs($learner)
        ->get(route('learner.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('learner/Dashboard')
            ->where('browseCourses', [])
            ->where('featuredCourses', [])
            ->has('myLearning', 1)
            ->where('myLearning.0.offering.name', 'Kelas A')
            ->where('myLearning.0.course_id', $course->id)
            ->where('facilitatedOfferings', [])
            ->where('academy.labels.offering', 'Kelas')
            ->where('academy.labels.facilitator', 'Dosen')
            ->where('academy.labels.learner', 'Mahasiswa')
        );
});

it('keeps facilitator grants and learner enrollments as separate lists', function () {
    Academy::using('academic');

    $user = User::factory()->create(['role' => 'learner']);
    $taught = Course::factory()->published()->create(['title' => 'Diajar']);
    $taken = Course::factory()->published()->create(['title' => 'Diikuti']);

    $kelasA = Offering::factory()->for($taught)->create([
        'name' => 'Kelas A',
        'code' => 'a',
        'facilitator_id' => $user->id,
    ]);
    $kelasMalam = Offering::factory()->for($taken)->create(['name' => 'Kelas Malam', 'code' => 'malam']);

    Enrollment::factory()->active()->create([
        'user_id' => $user->id,
        'course_id' => $taken->id,
        'offering_id' => $kelasMalam->id,
    ]);

    $this->actingAs($user)
        ->get(route('learner.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('learner/Dashboard')
            ->has('facilitatedOfferings', 1)
            ->where('facilitatedOfferings.0.id', $kelasA->id)
            ->has('myLearning', 1)
            ->where('myLearning.0.offering.name', 'Kelas Malam')
        );
});

it('leaves the lms admin dashboard unchanged', function () {
    Academy::using('academic');

    $admin = User::factory()->create(['role' => 'lms_admin']);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('stats')
        );
});
