<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_closed_by_default(): void
    {
        $this->get(route('register'))->assertNotFound();

        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_login_hides_sign_up_when_registration_is_closed(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('auth/Login')
                ->where('canRegister', false)
            );
    }

    public function test_registration_screen_can_be_rendered_when_enabled(): void
    {
        config(['lms.registration_enabled' => true]);

        $this->get(route('register'))->assertOk();
    }

    public function test_new_users_can_register_when_enabled(): void
    {
        config(['lms.registration_enabled' => true]);

        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_shows_sign_up_when_registration_is_enabled(): void
    {
        config(['lms.registration_enabled' => true]);

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('auth/Login')
                ->where('canRegister', true)
            );
    }
}
