<?php

use App\Models\Course;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('redirects guests to the login page', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('redirects learners to learner dashboard', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertRedirect(route('learner.dashboard'));
});

it('renders dashboard for content managers with stats', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    Course::factory()->count(2)->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('stats', fn (AssertableInertia $stats) => $stats
                ->where('courses', 2)
                ->where('learners', 0)
                ->where('programs', 0)
            )
        );
});

it('renders dashboard for trainers', function () {
    $user = User::factory()->create(['role' => 'trainer']);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('stats')
        );
});

it('renders dashboard for lms admins with global stats', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $owner = User::factory()->create(['role' => 'content_manager']);
    Course::factory()->count(3)->create(['user_id' => $owner->id]);
    User::factory()->count(2)->create(['role' => 'learner']);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('stats', fn (AssertableInertia $stats) => $stats
                ->where('courses', 3)
                ->where('learners', 2)
                ->where('programs', 0)
            )
        );
});
