<?php

use App\Models\Course;
use App\Models\Offering;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('stores an optional course code without changing offering codes', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);

    $this->actingAs($admin)
        ->post(route('courses.store'), [
            'title' => 'Algoritma dan Pemrograman',
            'difficulty_level' => 'beginner',
            'code' => 'IF101',
        ])
        ->assertRedirect();

    $course = Course::query()->where('title', 'Algoritma dan Pemrograman')->first();

    expect($course)->not->toBeNull()
        ->and($course->code)->toBe('IF101')
        ->and($course->slug)->not->toBe('IF101')
        ->and($course->offerings()->pluck('code')->all())->toBe([Offering::DEFAULT_CODE]);
});

it('allows creating a course without a code', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);

    $this->actingAs($admin)
        ->post(route('courses.store'), [
            'title' => 'Pengantar Agen AI',
            'difficulty_level' => 'beginner',
        ])
        ->assertRedirect();

    $course = Course::query()->where('title', 'Pengantar Agen AI')->first();

    expect($course)->not->toBeNull()
        ->and($course->code)->toBeNull()
        ->and($course->offerings()->pluck('code')->all())->toBe([Offering::DEFAULT_CODE]);
});

it('treats an empty course code as optional', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);

    $this->actingAs($admin)
        ->post(route('courses.store'), [
            'title' => 'Kursus Tanpa Kode',
            'difficulty_level' => 'beginner',
            'code' => '   ',
        ])
        ->assertRedirect();

    expect(Course::query()->where('title', 'Kursus Tanpa Kode')->value('code'))->toBeNull();
});

it('updates a course code without touching offering codes', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->draft()->create(['user_id' => $admin->id]);
    Offering::factory()->for($course)->create(['code' => 'a', 'name' => 'Kelas A']);

    $offeringCodes = $course->offerings()->orderBy('code')->pluck('code')->all();

    $this->actingAs($admin)
        ->put(route('courses.update', $course), [
            'title' => $course->title,
            'short_description' => $course->short_description,
            'difficulty_level' => $course->difficulty_level,
            'visibility' => $course->visibility,
            'code' => 'IF202',
        ])
        ->assertRedirect();

    expect($course->fresh()->code)->toBe('IF202')
        ->and($course->offerings()->orderBy('code')->pluck('code')->all())->toBe($offeringCodes);
});

it('exposes course code on the edit form', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->draft()->create([
        'user_id' => $admin->id,
        'code' => 'IF101',
    ]);

    $this->actingAs($admin)
        ->get(route('courses.edit', $course))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Edit')
            ->where('course.code', 'IF101')
        );
});

it('rejects a duplicate course code', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);
    Course::factory()->create(['code' => 'IF101']);

    $this->actingAs($admin)
        ->post(route('courses.store'), [
            'title' => 'Duplikat Kode',
            'difficulty_level' => 'beginner',
            'code' => 'IF101',
        ])
        ->assertSessionHasErrors('code');
});
