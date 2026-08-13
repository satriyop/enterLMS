<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

// =============================================================================
// Authorization Tests
// =============================================================================

it('redirects guests to login', function () {
    $this->get(route('my-learning'))
        ->assertRedirect(route('login'));
});

it('denies non-learner access', function () {
    asAdmin()
        ->get(route('my-learning'))
        ->assertForbidden();
});

it('denies admin access', function () {
    asAdmin()
        ->get(route('my-learning'))
        ->assertForbidden();
});

it('allows learner access', function () {
    asLearner()
        ->get(route('my-learning'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('enrollments/Index')
        );
});

// =============================================================================
// Data Display Tests
// =============================================================================

it('shows active and completed enrollments by default', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $course1 = Course::factory()->published()->create();
    $course2 = Course::factory()->published()->create();
    $course3 = Course::factory()->published()->create();

    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course1->id,
        'status' => 'active',
    ]);
    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course2->id,
        'status' => 'completed',
    ]);
    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course3->id,
        'status' => 'dropped',
    ]);

    $this->actingAs($user)
        ->get(route('my-learning'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('enrollments/Index')
            ->has('enrollments.data', 2)
            ->has('statusCounts', fn (AssertableInertia $counts) => $counts
                ->where('all', 3)
                ->where('active', 1)
                ->where('completed', 1)
                ->where('dropped', 1)
            )
        );
});

it('filters by active status', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $course1 = Course::factory()->published()->create();
    $course2 = Course::factory()->published()->create();

    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course1->id,
        'status' => 'active',
    ]);
    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course2->id,
        'status' => 'completed',
    ]);

    $this->actingAs($user)
        ->get(route('my-learning', ['status' => 'active']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('enrollments.data', 1)
            ->where('filters.status', 'active')
        );
});

it('filters by completed status', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $course1 = Course::factory()->published()->create();
    $course2 = Course::factory()->published()->create();

    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course1->id,
        'status' => 'active',
    ]);
    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course2->id,
        'status' => 'completed',
    ]);

    $this->actingAs($user)
        ->get(route('my-learning', ['status' => 'completed']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('enrollments.data', 1)
            ->where('filters.status', 'completed')
        );
});

it('filters by dropped status', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $course = Course::factory()->published()->create();

    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'dropped',
    ]);

    $this->actingAs($user)
        ->get(route('my-learning', ['status' => 'dropped']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('enrollments.data', 1)
            ->where('filters.status', 'dropped')
        );
});

it('ignores invalid status filter', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $course = Course::factory()->published()->create();

    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    // Invalid status falls back to showing active+completed
    $this->actingAs($user)
        ->get(route('my-learning', ['status' => 'invalid']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('enrollments.data', 1)
        );
});

it('only shows enrollments for the current user', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $otherUser = User::factory()->create(['role' => 'learner']);
    $course = Course::factory()->published()->create();

    Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    $course2 = Course::factory()->published()->create();
    Enrollment::factory()->create([
        'user_id' => $otherUser->id,
        'course_id' => $course2->id,
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get(route('my-learning'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('enrollments.data', 1)
        );
});

it('returns empty state when no enrollments exist', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->get(route('my-learning'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('enrollments.data', 0)
            ->has('statusCounts', fn (AssertableInertia $counts) => $counts
                ->where('all', 0)
                ->where('active', 0)
                ->where('completed', 0)
                ->where('dropped', 0)
            )
        );
});
