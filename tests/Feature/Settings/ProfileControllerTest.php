<?php

use App\Models\User;

it('redirects guests to login', function () {
    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});

it('shows profile page to authenticated user', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Profile')
            ->has('mustVerifyEmail')
        );
});

it('can update profile name and email', function () {
    $user = User::factory()->create([
        'name' => 'Nama Lama',
        'email' => 'lama@example.com',
        'role' => 'learner',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
        ])
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->name)->toBe('Nama Baru');
    expect($user->email)->toBe('baru@example.com');
});

it('resets email verification when email changed', function () {
    $user = User::factory()->create([
        'email' => 'lama@example.com',
        'email_verified_at' => now(),
        'role' => 'learner',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'baru@example.com',
        ])
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->toBeNull();
});

it('does not reset email verification when email unchanged', function () {
    $verifiedAt = now();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'email_verified_at' => $verifiedAt,
        'role' => 'learner',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'test@example.com',
        ])
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->email_verified_at->timestamp)->toBe($verifiedAt->timestamp);
});

it('cannot use duplicate email', function () {
    User::factory()->create(['email' => 'sudah-ada@example.com']);
    $user = User::factory()->create(['email' => 'user@example.com', 'role' => 'learner']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'sudah-ada@example.com',
        ])
        ->assertSessionHasErrors('email');

    expect($user->refresh()->email)->toBe('user@example.com');
});

it('requires name', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => '', 'email' => $user->email])
        ->assertSessionHasErrors('name');
});

it('requires email', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => ''])
        ->assertSessionHasErrors('email');
});

it('requires valid email', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => 'bukan-email'])
        ->assertSessionHasErrors('email');
});

it('can delete account', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

it('requires correct password to delete account', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password-salah'])
        ->assertSessionHasErrors('password');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});
