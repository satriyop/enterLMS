<?php

use App\Models\User;

describe('User Store Validation', function () {

    it('requires name', function () {
        asAdmin()
            ->post(route('admin.users.store'), [
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('name');
    });

    it('validates name max length', function (string $name, bool $shouldFail) {
        $response = asAdmin()
            ->post(route('admin.users.store'), [
                'name' => $name,
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'learner',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('name');
        } else {
            $response->assertSessionDoesntHaveErrors('name');
        }
    })->with([
        'at max (255)' => [str_repeat('a', 255), false],
        'exceeds max (256)' => [str_repeat('a', 256), true],
    ]);

    it('requires email', function () {
        asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('email');
    });

    it('validates email format', function (string $email, bool $shouldFail) {
        $response = asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => $email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'learner',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('email');
        } else {
            $response->assertSessionDoesntHaveErrors('email');
        }
    })->with([
        'valid email' => ['user@example.com', false],
        'missing @' => ['userexample.com', true],
        'missing domain' => ['user@', true],
        'missing username' => ['@example.com', true],
        'invalid format' => ['not-an-email', true],
    ]);

    it('validates email uniqueness', function () {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('email');
    });

    it('accepts unique email', function () {
        asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'unique@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'learner',
            ])
            ->assertSessionDoesntHaveErrors('email');
    });

    it('requires password', function () {
        asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password_confirmation' => 'password123',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('password');
    });

    it('validates password minimum length', function (string $password, bool $shouldFail) {
        $response = asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => $password,
                'password_confirmation' => $password,
                'role' => 'learner',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('password');
        } else {
            $response->assertSessionDoesntHaveErrors('password');
        }
    })->with([
        'at minimum (8)' => ['12345678', false],
        'above minimum (10)' => ['1234567890', false],
        'below minimum (7)' => ['1234567', true],
        'below minimum (5)' => ['12345', true],
    ]);

    it('requires password confirmation', function () {
        asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('password');
    });

    it('validates password confirmation matches', function () {
        asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different-password',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('password');
    });

    it('accepts matching password confirmation', function () {
        asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'learner',
            ])
            ->assertSessionDoesntHaveErrors('password');
    });

    it('requires role', function () {
        asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('role');
    });

    it('validates role enum', function (string $role, bool $shouldFail) {
        $response = asAdmin()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => $role,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('role');
        } else {
            $response->assertSessionDoesntHaveErrors('role');
        }
    })->with([
        'learner' => ['learner', false],
        'lms_admin' => ['lms_admin', false],
        'lms_admin' => ['lms_admin', false],
        'lms_admin' => ['lms_admin', false],
        'invalid role' => ['super_admin', true],
        'empty string' => ['', true],
    ]);

    it('rejects request from non-admin', function () {
        asLearner()
            ->post(route('admin.users.store'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'learner',
            ])
            ->assertForbidden();
    });

});

describe('User Update Validation', function () {

    it('requires name on update', function () {
        $user = User::factory()->create();

        asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => '',
                'email' => 'test@example.com',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('name');
    });

    it('validates name max length on update', function () {
        $user = User::factory()->create();

        asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => str_repeat('a', 256),
                'email' => 'test@example.com',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('name');
    });

    it('requires email on update', function () {
        $user = User::factory()->create();

        asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => 'Test User',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('email');
    });

    it('validates email format on update', function () {
        $user = User::factory()->create();

        asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => 'Test User',
                'email' => 'not-an-email',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('email');
    });

    it('validates email uniqueness on update ignoring current user', function () {
        $user = User::factory()->create(['email' => 'current@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        // Should allow keeping same email
        asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => 'Test User',
                'email' => 'current@example.com',
                'role' => 'learner',
            ])
            ->assertSessionDoesntHaveErrors('email');

        // Should reject another user's email
        asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => 'Test User',
                'email' => 'other@example.com',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('email');
    });

    it('allows update without password', function () {
        $user = User::factory()->create();

        asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'role' => 'learner',
            ])
            ->assertSessionDoesntHaveErrors('password');
    });

    it('validates password minimum length when provided on update', function (string $password, bool $shouldFail) {
        $user = User::factory()->create();

        $response = asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => $password,
                'password_confirmation' => $password,
                'role' => 'learner',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('password');
        } else {
            $response->assertSessionDoesntHaveErrors('password');
        }
    })->with([
        'at minimum (8)' => ['12345678', false],
        'above minimum (10)' => ['1234567890', false],
        'below minimum (7)' => ['1234567', true],
    ]);

    it('requires password confirmation when password provided on update', function () {
        $user = User::factory()->create();

        asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('password');
    });

    it('validates password confirmation matches on update', function () {
        $user = User::factory()->create();

        asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different-password',
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('password');
    });

    it('requires role on update', function () {
        $user = User::factory()->create();

        asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])
            ->assertSessionHasErrors('role');
    });

    it('validates role enum on update', function (string $role, bool $shouldFail) {
        $user = User::factory()->create();

        $response = asAdmin()
            ->put(route('admin.users.update', $user), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'role' => $role,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('role');
        } else {
            $response->assertSessionDoesntHaveErrors('role');
        }
    })->with([
        'learner' => ['learner', false],
        'lms_admin' => ['lms_admin', false],
        'lms_admin' => ['lms_admin', false],
        'lms_admin' => ['lms_admin', false],
        'invalid role' => ['super_admin', true],
    ]);

    it('prevents changing own role', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);

        test()->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => 'Admin User',
                'email' => $admin->email,
                'role' => 'learner',
            ])
            ->assertSessionHasErrors('role');
    });

    it('allows changing own role to same role', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);

        test()->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => 'Admin User',
                'email' => $admin->email,
                'role' => 'lms_admin',
            ])
            ->assertSessionDoesntHaveErrors('role');
    });

    it('allows admin to change other users role', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $otherUser = User::factory()->create(['role' => 'learner']);

        test()->actingAs($admin)
            ->put(route('admin.users.update', $otherUser), [
                'name' => 'Other User',
                'email' => $otherUser->email,
                'role' => 'lms_admin',
            ])
            ->assertSessionDoesntHaveErrors('role');
    });

    it('rejects request from non-admin', function () {
        $user = User::factory()->create();

        asLearner()
            ->put(route('admin.users.update', $user), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'role' => 'learner',
            ])
            ->assertForbidden();
    });

});
