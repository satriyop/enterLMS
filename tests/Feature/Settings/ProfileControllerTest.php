<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile_page(): void
    {
        $response = $this->get(route('profile.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/Profile')
            ->has('mustVerifyEmail')
        );
    }

    public function test_user_can_update_profile_name_and_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@example.com',
            'role' => 'learner',
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertEquals('Nama Baru', $user->name);
        $this->assertEquals('baru@example.com', $user->email);
    }

    public function test_email_verification_is_reset_when_email_changed(): void
    {
        $user = User::factory()->create([
            'email' => 'lama@example.com',
            'email_verified_at' => now(),
            'role' => 'learner',
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'baru@example.com',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_not_reset_when_email_unchanged(): void
    {
        $verifiedAt = now();
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => $verifiedAt,
            'role' => 'learner',
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals($verifiedAt->timestamp, $user->email_verified_at->timestamp);
    }

    public function test_user_cannot_use_duplicate_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'sudah-ada@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'user@example.com',
            'role' => 'learner',
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'sudah-ada@example.com',
        ]);

        $response->assertSessionHasErrors('email');

        $user->refresh();
        $this->assertEquals('user@example.com', $user->email);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => '',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_email_is_required(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => '',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_email_must_be_valid(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'bukan-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($user)->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_must_provide_correct_password_to_delete_account(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($user)->delete(route('profile.destroy'), [
            'password' => 'password-salah',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
