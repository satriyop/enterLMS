<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\TagPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagPolicyTest extends TestCase
{
    use RefreshDatabase;

    private TagPolicy $policy;

    private User $lmsAdmin;

    private User $contentManager;

    private User $learner;

    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TagPolicy;

        $this->lmsAdmin = User::factory()->create(['role' => 'lms_admin']);
        $this->contentManager = User::factory()->create(['role' => 'content_manager']);
        $this->learner = User::factory()->create(['role' => 'learner']);
        $this->trainer = User::factory()->create(['role' => 'trainer']);
    }

    // ========== viewAny ==========

    public function test_any_user_can_view_tags(): void
    {
        $this->assertTrue($this->policy->viewAny($this->lmsAdmin));
        $this->assertTrue($this->policy->viewAny($this->contentManager));
        $this->assertTrue($this->policy->viewAny($this->learner));
        $this->assertTrue($this->policy->viewAny($this->trainer));
    }

    // ========== create ==========

    public function test_admin_can_create_tag(): void
    {
        $this->assertTrue($this->policy->create($this->lmsAdmin));
    }

    public function test_content_manager_can_create_tag(): void
    {
        $this->assertTrue($this->policy->create($this->contentManager));
    }

    public function test_learner_cannot_create_tag(): void
    {
        $this->assertFalse($this->policy->create($this->learner));
    }

    public function test_trainer_cannot_create_tag(): void
    {
        $this->assertFalse($this->policy->create($this->trainer));
    }

    // ========== update ==========

    public function test_admin_can_update_tag(): void
    {
        $this->assertTrue($this->policy->update($this->lmsAdmin));
    }

    public function test_content_manager_cannot_update_tag(): void
    {
        $this->assertFalse($this->policy->update($this->contentManager));
    }

    // ========== delete ==========

    public function test_admin_can_delete_tag(): void
    {
        $this->assertTrue($this->policy->delete($this->lmsAdmin));
    }

    public function test_content_manager_cannot_delete_tag(): void
    {
        $this->assertFalse($this->policy->delete($this->contentManager));
    }

    public function test_learner_cannot_delete_tag(): void
    {
        $this->assertFalse($this->policy->delete($this->learner));
    }
}
