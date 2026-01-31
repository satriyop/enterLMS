<?php

namespace Tests\Unit\Policies;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Policies\EnrollmentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for EnrollmentPolicy.
 *
 * Test Matrix:
 * - User Roles: lms_admin, content_manager, learner, trainer
 * - Enrollment Status: active, completed, dropped
 * - Ownership: owner vs non-owner vs course-owner
 */
class EnrollmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private EnrollmentPolicy $policy;

    private User $lmsAdmin;

    private User $contentManager;

    private User $learner;

    private User $otherLearner;

    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new EnrollmentPolicy;

        $this->lmsAdmin = User::factory()->create(['role' => 'lms_admin']);
        $this->contentManager = User::factory()->create(['role' => 'content_manager']);
        $this->learner = User::factory()->create(['role' => 'learner']);
        $this->otherLearner = User::factory()->create(['role' => 'learner']);
        $this->trainer = User::factory()->create(['role' => 'trainer']);
    }

    // ========== viewAny ==========

    public function test_any_user_can_view_enrollments(): void
    {
        $this->assertTrue($this->policy->viewAny($this->lmsAdmin));
        $this->assertTrue($this->policy->viewAny($this->learner));
        $this->assertTrue($this->policy->viewAny($this->trainer));
    }

    // ========== view ==========

    public function test_admin_can_view_any_enrollment(): void
    {
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertTrue($this->policy->view($this->lmsAdmin, $enrollment));
    }

    public function test_owner_can_view_own_enrollment(): void
    {
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertTrue($this->policy->view($this->learner, $enrollment));
    }

    public function test_course_owner_can_view_enrollment_in_their_course(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->contentManager->id,
        ]);
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
            'course_id' => $course->id,
        ]);

        $this->assertTrue($this->policy->view($this->contentManager, $enrollment));
    }

    public function test_other_learner_cannot_view_enrollment(): void
    {
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertFalse($this->policy->view($this->otherLearner, $enrollment));
    }

    // ========== drop ==========

    public function test_owner_can_drop_active_enrollment(): void
    {
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertTrue($this->policy->drop($this->learner, $enrollment));
    }

    public function test_admin_can_drop_any_active_enrollment(): void
    {
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertTrue($this->policy->drop($this->lmsAdmin, $enrollment));
    }

    public function test_owner_cannot_drop_completed_enrollment(): void
    {
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertFalse($this->policy->drop($this->learner, $enrollment));
    }

    public function test_owner_cannot_drop_already_dropped_enrollment(): void
    {
        $enrollment = Enrollment::factory()->dropped()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertFalse($this->policy->drop($this->learner, $enrollment));
    }

    public function test_other_learner_cannot_drop_enrollment(): void
    {
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertFalse($this->policy->drop($this->otherLearner, $enrollment));
    }

    // ========== complete ==========

    public function test_admin_can_complete_active_enrollment(): void
    {
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertTrue($this->policy->complete($this->lmsAdmin, $enrollment));
    }

    public function test_learner_cannot_manually_complete_own_enrollment(): void
    {
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertFalse($this->policy->complete($this->learner, $enrollment));
    }

    public function test_cannot_complete_dropped_enrollment(): void
    {
        $enrollment = Enrollment::factory()->dropped()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertFalse($this->policy->complete($this->lmsAdmin, $enrollment));
    }

    // ========== reactivate ==========

    public function test_owner_can_reactivate_dropped_enrollment(): void
    {
        $enrollment = Enrollment::factory()->dropped()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertTrue($this->policy->reactivate($this->learner, $enrollment));
    }

    public function test_admin_can_reactivate_any_dropped_enrollment(): void
    {
        $enrollment = Enrollment::factory()->dropped()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertTrue($this->policy->reactivate($this->lmsAdmin, $enrollment));
    }

    public function test_cannot_reactivate_active_enrollment(): void
    {
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertFalse($this->policy->reactivate($this->learner, $enrollment));
    }

    public function test_cannot_reactivate_completed_enrollment(): void
    {
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertFalse($this->policy->reactivate($this->learner, $enrollment));
    }

    public function test_other_learner_cannot_reactivate_enrollment(): void
    {
        $enrollment = Enrollment::factory()->dropped()->create([
            'user_id' => $this->learner->id,
        ]);

        $this->assertFalse($this->policy->reactivate($this->otherLearner, $enrollment));
    }
}
