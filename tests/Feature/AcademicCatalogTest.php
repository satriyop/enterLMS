<?php

use App\Domain\Shared\Academy;
use App\Models\Course;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('does not list Open Courses on the home catalog when offerings are enabled', function () {
    Academy::using('academic');

    Course::factory()->published()->public()->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Welcome')
            ->where('featuredCourses', [])
            ->where('popularCourses', [])
            ->where('canRegister', false)
        );
});

it('still lists Open Courses on the home catalog for the academy preset', function () {
    $course = Course::factory()->published()->public()->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Welcome')
            ->where('featuredCourses.0.id', $course->id)
        );
});

it('does not let learners browse the public catalog when offerings are enabled', function () {
    Academy::using('academic');

    Course::factory()->published()->public()->create();
    $learner = User::factory()->create(['role' => 'learner']);

    $this->actingAs($learner)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Browse')
            ->where('courses.data', [])
        );
});

it('lets learners browse Open Courses when offerings are off', function () {
    $course = Course::factory()->published()->public()->create();
    $learner = User::factory()->create(['role' => 'learner']);

    $this->actingAs($learner)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Browse')
            ->where('courses.data.0.id', $course->id)
        );
});

it('lets a learner self-enroll an Open Course on the academy preset', function () {
    $course = Course::factory()->published()->public()->create();
    $learner = User::factory()->create(['role' => 'learner']);

    $this->actingAs($learner)
        ->post(route('courses.enroll', $course))
        ->assertRedirect();

    $this->assertDatabaseHas('enrollments', [
        'user_id' => $learner->id,
        'course_id' => $course->id,
    ]);
});
