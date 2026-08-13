<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\CategoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CategoryPolicy $policy;

    private User $lmsAdmin;

    private User $contentManager;

    private User $learner;

    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CategoryPolicy;

        $this->lmsAdmin = User::factory()->create(['role' => 'lms_admin']);
        $this->contentManager = User::factory()->create(['role' => 'lms_admin']);
        $this->learner = User::factory()->create(['role' => 'learner']);
        $this->trainer = User::factory()->create(['role' => 'lms_admin']);
    }

    // ========== viewAny ==========

    public function test_any_user_can_view_categories(): void
    {
        $this->assertTrue($this->policy->viewAny($this->lmsAdmin));
        $this->assertTrue($this->policy->viewAny($this->contentManager));
        $this->assertTrue($this->policy->viewAny($this->learner));
        $this->assertTrue($this->policy->viewAny($this->trainer));
    }

    // ========== create ==========

    public function test_admin_can_create_category(): void
    {
        $this->assertTrue($this->policy->create($this->lmsAdmin));
    }

    public function test_learner_cannot_create_category(): void
    {
        $this->assertFalse($this->policy->create($this->learner));
    }

    // ========== update ==========

    public function test_admin_can_update_category(): void
    {
        $this->assertTrue($this->policy->update($this->lmsAdmin));
    }

    public function test_learner_cannot_update_category(): void
    {
        $this->assertFalse($this->policy->update($this->learner));
    }

    // ========== delete ==========

    public function test_admin_can_delete_category(): void
    {
        $this->assertTrue($this->policy->delete($this->lmsAdmin));
    }

    public function test_learner_cannot_delete_category(): void
    {
        $this->assertFalse($this->policy->delete($this->learner));
    }
}
