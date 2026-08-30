<?php

use App\Domain\Shared\Academy;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Offering;
use App\Models\Question;
use App\Models\User;
use App\Policies\AssessmentAttemptPolicy;
use App\Policies\ConversationPolicy;

beforeEach(function () {
    Academy::using('academic');

    $this->admin = User::factory()->create(['role' => 'lms_admin']);
    $this->facilitator = User::factory()->create(['role' => 'learner']);
    $this->otherFacilitator = User::factory()->create(['role' => 'learner']);
    $this->learner = User::factory()->create(['role' => 'learner']);

    $this->course = Course::factory()->published()->public()->create(['user_id' => $this->admin->id]);
    $this->offering = Offering::factory()->for($this->course)->create([
        'name' => 'Kelas A',
        'facilitator_id' => $this->facilitator->id,
    ]);
    $this->otherOffering = Offering::factory()->for($this->course)->create([
        'name' => 'Kelas B',
        'facilitator_id' => $this->otherFacilitator->id,
    ]);

    $this->enrollment = Enrollment::factory()->active()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
        'offering_id' => $this->offering->id,
    ]);

    $this->assessment = Assessment::factory()->published()->create([
        'course_id' => $this->course->id,
        'user_id' => $this->admin->id,
        'time_limit_minutes' => null,
    ]);
});

it('assigns a facilitator by email on an offering', function () {
    $this->actingAs($this->admin)
        ->put(route('courses.offerings.update', [$this->course, $this->course->ensureDefaultOffering()]), [
            'name' => $this->course->title,
            'facilitator_email' => $this->facilitator->email,
        ])
        ->assertRedirect();

    expect($this->course->ensureDefaultOffering()->fresh()->facilitator_id)->toBe($this->facilitator->id);
});

it('lets the offering facilitator grade a submitted attempt on their roster', function () {
    $attempt = AssessmentAttempt::factory()->submitted()->create([
        'assessment_id' => $this->assessment->id,
        'user_id' => $this->learner->id,
        'enrollment_id' => $this->enrollment->id,
    ]);

    $policy = new AssessmentAttemptPolicy;

    expect($policy->grade($this->facilitator, $attempt, $this->assessment, $this->course))->toBeTrue();

    $this->actingAs($this->facilitator)
        ->get(route('assessments.grade', [$this->course, $this->assessment, $attempt]))
        ->assertOk();
});

it('does not let a facilitator grade an attempt from another offering', function () {
    $attempt = AssessmentAttempt::factory()->submitted()->create([
        'assessment_id' => $this->assessment->id,
        'user_id' => $this->learner->id,
        'enrollment_id' => $this->enrollment->id,
    ]);

    $policy = new AssessmentAttemptPolicy;

    expect($policy->grade($this->otherFacilitator, $attempt, $this->assessment, $this->course))->toBeFalse();

    $this->actingAs($this->otherFacilitator)
        ->get(route('assessments.grade', [$this->course, $this->assessment, $attempt]))
        ->assertForbidden();
});

it('does not let a facilitator grade their own learner attempt on a course they do not facilitate', function () {
    $bystander = User::factory()->create(['role' => 'learner']);
    $attempt = AssessmentAttempt::factory()->submitted()->create([
        'assessment_id' => $this->assessment->id,
        'user_id' => $this->learner->id,
        'enrollment_id' => $this->enrollment->id,
    ]);

    expect((new AssessmentAttemptPolicy)->grade($bystander, $attempt, $this->assessment, $this->course))->toBeFalse();
});

it('lets the facilitator accept a grade proposal', function () {
    $question = Question::factory()->essay()->create([
        'assessment_id' => $this->assessment->id,
        'points' => 20,
    ]);

    $this->actingAs($this->learner)
        ->post(route('assessments.start', [$this->course, $this->assessment]))
        ->assertRedirect();

    $attempt = AssessmentAttempt::query()->where('user_id', $this->learner->id)->first();
    expect($attempt->enrollment_id)->toBe($this->enrollment->id);

    $this->actingAs($this->learner)
        ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $attempt]), [
            'answers' => [
                ['question_id' => $question->id, 'answer_text' => 'Esai tentang agen.'],
            ],
        ])
        ->assertRedirect();

    $answer = AttemptAnswer::query()->first();

    $this->actingAs($this->facilitator)
        ->post(route('assessments.grade.proposal.accept', [$this->course, $this->assessment, $attempt, $answer]))
        ->assertRedirect();

    expect($answer->fresh()->proposal_status)->toBe('accepted')
        ->and($answer->fresh()->graded_at)->not->toBeNull();
});

it('lets the facilitator read a conversation on their offering and not on another', function () {
    $section = CourseSection::factory()->create(['course_id' => $this->course->id]);
    $lesson = Lesson::factory()->text()->create(['course_section_id' => $section->id]);
    $conversation = Conversation::factory()->create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $lesson->id,
    ]);

    $policy = new ConversationPolicy;

    expect($policy->view($this->facilitator, $conversation))->toBeTrue()
        ->and($policy->view($this->otherFacilitator, $conversation))->toBeFalse();

    $this->actingAs($this->facilitator)
        ->get(route('courses.lessons.conversation.show', [
            'course' => $this->course,
            'lesson' => $lesson,
            'enrollment_id' => $this->enrollment->id,
        ]))
        ->assertOk();

    $this->actingAs($this->otherFacilitator)
        ->get(route('courses.lessons.conversation.show', [
            'course' => $this->course,
            'lesson' => $lesson,
            'enrollment_id' => $this->enrollment->id,
        ]))
        ->assertForbidden();
});

it('does not let a facilitator publish assessments', function () {
    $this->actingAs($this->facilitator)
        ->post(route('assessments.publish', [$this->course, $this->assessment]))
        ->assertForbidden();
});
