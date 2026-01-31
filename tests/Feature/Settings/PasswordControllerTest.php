<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_password_page(): void
    {
        $response = $this->get(route('user-password.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_password_page(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($user)->get(route('user-password.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/Password')
        );
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password-lama',
            'role' => 'learner',
        ]);

        $response = $this->actingAs($user)->put(route('user-password.update'), [
            'current_password' => 'password-lama',
            'password' => 'password-baru123',
            'password_confirmation' => 'password-baru123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('password-baru123', $user->password));
    }

    public function test_user_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password-lama',
            'role' => 'learner',
        ]);

        $response = $this->actingAs($user)->put(route('user-password.update'), [
            'current_password' => 'password-salah',
            'password' => 'password-baru123',
            'password_confirmation' => 'password-baru123',
        ]);

        $response->assertSessionHasErrors('current_password');

        $user->refresh();
        $this->assertTrue(Hash::check('password-lama', $user->password));
    }

    public function test_password_must_be_confirmed(): void
    {
        $user = User::factory()->create([
            'password' => 'password-lama',
            'role' => 'learner',
        ]);

        $response = $this->actingAs($user)->put(route('user-password.update'), [
            'current_password' => 'password-lama',
            'password' => 'password-baru123',
            'password_confirmation' => 'password-berbeda',
        ]);

        $response->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check('password-lama', $user->password));
    }

    public function test_current_password_is_required(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($user)->put(route('user-password.update'), [
            'current_password' => '',
            'password' => 'password-baru123',
            'password_confirmation' => 'password-baru123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_new_password_is_required(): void
    {
        $user = User::factory()->create([
            'password' => 'password-lama',
            'role' => 'learner',
        ]);

        $response = $this->actingAs($user)->put(route('user-password.update'), [
            'current_password' => 'password-lama',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_must_meet_minimum_requirements(): void
    {
        $user = User::factory()->create([
            'password' => 'password-lama',
            'role' => 'learner',
        ]);

        $response = $this->actingAs($user)->put(route('user-password.update'), [
            'current_password' => 'password-lama',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
