<?php

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

// =============================================================================
// Helper
// =============================================================================

function createNotification(User $user, array $overrides = []): DatabaseNotification
{
    return DatabaseNotification::create(array_merge([
        'id' => Str::uuid()->toString(),
        'type' => 'App\\Domain\\Enrollment\\Notifications\\WelcomeToCourseMail',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [
            'type' => 'enrollment.welcome',
            'course_id' => 1,
            'course_title' => 'Kursus Test',
            'message' => 'Anda berhasil mendaftar di kursus "Kursus Test"',
        ],
        'read_at' => null,
    ], $overrides));
}

// =============================================================================
// Authorization Tests
// =============================================================================

it('redirects guests to login when viewing notifications', function () {
    $this->get(route('notifications.index'))
        ->assertRedirect(route('login'));
});

it('allows authenticated users to view notifications', function () {
    asLearner()
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('notifications/Index')
            ->has('notifications')
        );
});

it('allows admins to view notifications', function () {
    asAdmin()
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('notifications/Index')
        );
});

// =============================================================================
// Index Tests
// =============================================================================

it('shows user notifications paginated', function () {
    $user = User::factory()->create(['role' => 'learner']);

    createNotification($user);
    createNotification($user, ['id' => Str::uuid()->toString()]);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('notifications/Index')
            ->has('notifications.data', 2)
        );
});

it('does not show other users notifications', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $otherUser = User::factory()->create(['role' => 'learner']);

    createNotification($user);
    createNotification($otherUser, ['id' => Str::uuid()->toString()]);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('notifications.data', 1)
        );
});

it('orders notifications newest first', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $older = createNotification($user, [
        'created_at' => now()->subHour(),
    ]);
    $newer = createNotification($user, [
        'id' => Str::uuid()->toString(),
        'created_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('notifications.data.0.id', $newer->id)
            ->where('notifications.data.1.id', $older->id)
        );
});

// =============================================================================
// Mark As Read Tests
// =============================================================================

it('marks a notification as read', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $notification = createNotification($user);

    expect($notification->read_at)->toBeNull();

    $this->actingAs($user)
        ->patch(route('notifications.markAsRead', $notification->id))
        ->assertRedirect();

    $notification->refresh();
    expect($notification->read_at)->not->toBeNull();
});

it('returns 404 for other users notification', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $otherUser = User::factory()->create(['role' => 'learner']);
    $notification = createNotification($otherUser);

    $this->actingAs($user)
        ->patch(route('notifications.markAsRead', $notification->id))
        ->assertNotFound();
});

// =============================================================================
// Mark All Read Tests
// =============================================================================

it('marks all notifications as read', function () {
    $user = User::factory()->create(['role' => 'learner']);

    createNotification($user);
    createNotification($user, ['id' => Str::uuid()->toString()]);

    expect($user->unreadNotifications()->count())->toBe(2);

    $this->actingAs($user)
        ->post(route('notifications.markAllRead'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('does not affect other users notifications when marking all read', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $otherUser = User::factory()->create(['role' => 'learner']);

    createNotification($user);
    createNotification($otherUser, ['id' => Str::uuid()->toString()]);

    $this->actingAs($user)
        ->post(route('notifications.markAllRead'))
        ->assertRedirect();

    expect($otherUser->unreadNotifications()->count())->toBe(1);
});

// =============================================================================
// Shared Data Tests
// =============================================================================

it('shares unread notification count via Inertia', function () {
    $user = User::factory()->create(['role' => 'learner']);

    createNotification($user);
    createNotification($user, ['id' => Str::uuid()->toString()]);
    createNotification($user, [
        'id' => Str::uuid()->toString(),
        'read_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('unreadNotificationsCount', 2)
        );
});

it('returns 0 unread count for guests', function () {
    // This verifies the middleware handles null user gracefully
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('unreadNotificationsCount', 0)
        );
});
