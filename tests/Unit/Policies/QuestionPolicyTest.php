<?php

namespace Tests\Unit\Policies;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Question;
use App\Models\User;
use App\Policies\QuestionPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Unit tests for QuestionPolicy.
 *
 * Tests verify authorization logic for question management operations.
 * All policy methods delegate to the parent assessment's course update permission.
 *
 * Note: Methods that delegate to AssessmentPolicy via Gate::allows() require
 * actingAs() so the inner Gate call resolves the correct user.
 */
beforeEach(function () {
    $this->policy = new QuestionPolicy;

    $this->lmsAdmin = User::factory()->create(['role' => 'lms_admin']);
    $this->contentManager = User::factory()->create(['role' => 'content_manager']);
    $this->otherContentManager = User::factory()->create(['role' => 'content_manager']);
    $this->learner = User::factory()->create(['role' => 'learner']);

    // Create DRAFT course for content_manager to be able to update
    $this->course = Course::factory()->draft()->create(['user_id' => $this->contentManager->id]);
    $this->assessment = Assessment::factory()->create([
        'course_id' => $this->course->id,
        'user_id' => $this->contentManager->id,
        'status' => 'draft',
    ]);
    $this->question = Question::factory()->create(['assessment_id' => $this->assessment->id]);

    // Create assessment owned by other content manager
    $this->otherCourse = Course::factory()->draft()->create(['user_id' => $this->otherContentManager->id]);
    $this->otherAssessment = Assessment::factory()->create([
        'course_id' => $this->otherCourse->id,
        'user_id' => $this->otherContentManager->id,
        'status' => 'draft',
    ]);
    $this->otherQuestion = Question::factory()->create(['assessment_id' => $this->otherAssessment->id]);
});

// ========== viewAny (delegates to AssessmentPolicy::update via Gate) ==========

it('allows lms_admin to view any questions for any assessment', function () {
    $this->actingAs($this->lmsAdmin);
    expect(Gate::check('viewAny', [Question::class, $this->assessment]))->toBeTrue();
    expect(Gate::check('viewAny', [Question::class, $this->otherAssessment]))->toBeTrue();
});

it('allows content_manager to view any questions for their own assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::check('viewAny', [Question::class, $this->assessment]))->toBeTrue();
});

it('denies content_manager to view any questions for other assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::denies('viewAny', [Question::class, $this->otherAssessment]))->toBeTrue();
});

it('denies learner to view any questions', function () {
    $this->actingAs($this->learner);
    expect(Gate::denies('viewAny', [Question::class, $this->assessment]))->toBeTrue();
});

// ========== view (delegates to AssessmentPolicy::view via Gate) ==========

it('allows lms_admin to view any question', function () {
    $this->actingAs($this->lmsAdmin);
    expect(Gate::check('view', $this->question))->toBeTrue();
    expect(Gate::check('view', $this->otherQuestion))->toBeTrue();
});

it('allows content_manager to view questions from their own assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::check('view', $this->question))->toBeTrue();
});

it('denies content_manager to view questions from other assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::denies('view', $this->otherQuestion))->toBeTrue();
});

// ========== create (delegates to AssessmentPolicy::update via Gate) ==========

it('allows lms_admin to create questions in any assessment', function () {
    $this->actingAs($this->lmsAdmin);
    expect(Gate::check('create', [Question::class, $this->assessment]))->toBeTrue();
    expect(Gate::check('create', [Question::class, $this->otherAssessment]))->toBeTrue();
});

it('allows content_manager to create questions in their own assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::check('create', [Question::class, $this->assessment]))->toBeTrue();
});

it('denies content_manager to create questions in other assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::denies('create', [Question::class, $this->otherAssessment]))->toBeTrue();
});

it('denies learner to create questions', function () {
    $this->actingAs($this->learner);
    expect(Gate::denies('create', [Question::class, $this->assessment]))->toBeTrue();
});

// ========== update (delegates to AssessmentPolicy::update via Gate) ==========

it('allows lms_admin to update any question', function () {
    $this->actingAs($this->lmsAdmin);
    expect(Gate::check('update', $this->question))->toBeTrue();
    expect(Gate::check('update', $this->otherQuestion))->toBeTrue();
});

it('allows content_manager to update questions in their own assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::check('update', $this->question))->toBeTrue();
});

it('denies content_manager to update questions in other assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::denies('update', $this->otherQuestion))->toBeTrue();
});

it('denies learner to update questions', function () {
    $this->actingAs($this->learner);
    expect(Gate::denies('update', $this->question))->toBeTrue();
});

// ========== delete (delegates to AssessmentPolicy::update via Gate) ==========

it('allows lms_admin to delete any question', function () {
    $this->actingAs($this->lmsAdmin);
    expect(Gate::check('delete', $this->question))->toBeTrue();
    expect(Gate::check('delete', $this->otherQuestion))->toBeTrue();
});

it('allows content_manager to delete questions in their own assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::check('delete', $this->question))->toBeTrue();
});

it('denies content_manager to delete questions in other assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::denies('delete', $this->otherQuestion))->toBeTrue();
});

it('denies learner to delete questions', function () {
    $this->actingAs($this->learner);
    expect(Gate::denies('delete', $this->question))->toBeTrue();
});

// ========== reorder (delegates to AssessmentPolicy::update via Gate) ==========

it('allows lms_admin to reorder questions in any assessment', function () {
    $this->actingAs($this->lmsAdmin);
    expect(Gate::check('reorder', [Question::class, $this->assessment]))->toBeTrue();
    expect(Gate::check('reorder', [Question::class, $this->otherAssessment]))->toBeTrue();
});

it('allows content_manager to reorder questions in their own assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::check('reorder', [Question::class, $this->assessment]))->toBeTrue();
});

it('denies content_manager to reorder questions in other assessment', function () {
    $this->actingAs($this->contentManager);
    expect(Gate::denies('reorder', [Question::class, $this->otherAssessment]))->toBeTrue();
});

it('denies learner to reorder questions', function () {
    $this->actingAs($this->learner);
    expect(Gate::denies('reorder', [Question::class, $this->assessment]))->toBeTrue();
});
