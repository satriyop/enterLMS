<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_learners_are_redirected_to_learner_dashboard()
    {
        $user = User::factory()->create(['role' => 'learner']);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('learner.dashboard'));
    }

    public function test_content_managers_can_visit_the_dashboard()
    {
        $user = User::factory()->create(['role' => 'content_manager']);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_trainers_can_visit_the_dashboard()
    {
        $user = User::factory()->create(['role' => 'trainer']);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_lms_admins_can_visit_the_dashboard()
    {
        $user = User::factory()->create(['role' => 'lms_admin']);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }
}
