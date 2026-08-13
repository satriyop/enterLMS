<?php

namespace Tests\Unit\Policies;

use App\Models\LearningPath;
use App\Models\User;
use App\Policies\LearningPathPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for LearningPathPolicy.
 *
 * These tests verify authorization logic for learning path operations.
 *
 * Test Matrix:
 * - User Roles: lms_admin, content_manager, learner
 * - Published Status: published, unpublished
 * - Ownership: creator vs non-creator
 */
class LearningPathPolicyTest extends TestCase
{
    use RefreshDatabase;

    private LearningPathPolicy $policy;

    private User $lmsAdmin;

    private User $contentManager;

    private User $otherContentManager;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new LearningPathPolicy;

        $this->lmsAdmin = User::factory()->create(['role' => 'lms_admin']);
        $this->contentManager = User::factory()->create(['role' => 'lms_admin']);
        $this->otherContentManager = User::factory()->create(['role' => 'lms_admin']);
        $this->learner = User::factory()->create(['role' => 'learner']);
    }

    // ========== viewAny ==========

    public function test_any_authenticated_user_can_view_any_learning_paths(): void
    {
        $this->assertTrue($this->policy->viewAny($this->lmsAdmin));
        $this->assertTrue($this->policy->viewAny($this->contentManager));
        $this->assertTrue($this->policy->viewAny($this->learner));
    }

    // ========== view ==========

    public function test_lms_admin_can_view_any_learning_path(): void
    {
        $unpublished = LearningPath::factory()->unpublished()->create();
        $published = LearningPath::factory()->published()->create();

        $this->assertTrue($this->policy->view($this->lmsAdmin, $unpublished));
        $this->assertTrue($this->policy->view($this->lmsAdmin, $published));
    }

    public function test_learner_can_view_published_learning_path(): void
    {
        $learningPath = LearningPath::factory()->published()->create();

        $this->assertTrue($this->policy->view($this->learner, $learningPath));
    }

    public function test_learner_cannot_view_unpublished_learning_path(): void
    {
        $learningPath = LearningPath::factory()->unpublished()->create();

        $this->assertFalse($this->policy->view($this->learner, $learningPath));
    }

    // ========== create ==========

    public function test_lms_admin_can_create_learning_path(): void
    {
        $this->assertTrue($this->policy->create($this->lmsAdmin));
    }

    public function test_learner_cannot_create_learning_path(): void
    {
        $this->assertFalse($this->policy->create($this->learner));
    }

    // ========== update ==========

    public function test_lms_admin_can_update_any_learning_path(): void
    {
        $unpublished = LearningPath::factory()->unpublished()->create();
        $published = LearningPath::factory()->published()->create();

        $this->assertTrue($this->policy->update($this->lmsAdmin, $unpublished));
        $this->assertTrue($this->policy->update($this->lmsAdmin, $published));
    }

    public function test_learner_cannot_update_learning_path(): void
    {
        $learningPath = LearningPath::factory()->unpublished()->create();

        $this->assertFalse($this->policy->update($this->learner, $learningPath));
    }

    // ========== delete ==========

    public function test_lms_admin_can_delete_any_learning_path(): void
    {
        $unpublished = LearningPath::factory()->unpublished()->create();
        $published = LearningPath::factory()->published()->create();

        $this->assertTrue($this->policy->delete($this->lmsAdmin, $unpublished));
        $this->assertTrue($this->policy->delete($this->lmsAdmin, $published));
    }

    public function test_learner_cannot_delete_learning_path(): void
    {
        $learningPath = LearningPath::factory()->unpublished()->create();

        $this->assertFalse($this->policy->delete($this->learner, $learningPath));
    }

    // ========== publish ==========

    public function test_lms_admin_can_publish_any_learning_path(): void
    {
        $learningPath = LearningPath::factory()->unpublished()->create();

        $this->assertTrue($this->policy->publish($this->lmsAdmin, $learningPath));
    }

    public function test_learner_cannot_publish_learning_path(): void
    {
        $learningPath = LearningPath::factory()->unpublished()->create();

        $this->assertFalse($this->policy->publish($this->learner, $learningPath));
    }

    // ========== reorder ==========

    public function test_reorder_follows_update_policy(): void
    {
        $path = LearningPath::factory()->unpublished()->create([
            'created_by' => $this->lmsAdmin->id,
        ]);

        $this->assertTrue($this->policy->reorder($this->lmsAdmin, $path));
        $this->assertFalse($this->policy->reorder($this->learner, $path));
    }

    // ========== canManageLearningPaths Helper ==========

    public function test_can_manage_learning_paths_helper(): void
    {
        $this->assertTrue($this->lmsAdmin->canManageLearningPaths());
        $this->assertFalse($this->learner->canManageLearningPaths());
    }
}
