<?php

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function proposalCourse(): array
{
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->published()->public()->create(['user_id' => $admin->id]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create(['course_section_id' => $section->id]);

    return compact('admin', 'course', 'section', 'lesson');
}

describe('Grade Proposal', function () {
    it('proposes on short-answer without acceptable answers and hides the score from the Learner', function () {
        ['admin' => $admin, 'course' => $course] = proposalCourse();
        ['user' => $user] = createEnrolledLearner($course);

        $assessment = Assessment::factory()->published()->create([
            'course_id' => $course->id,
            'user_id' => $admin->id,
            'time_limit_minutes' => null,
            'passing_score' => 70,
        ]);
        $question = Question::factory()->shortAnswer()->create([
            'assessment_id' => $assessment->id,
            'points' => 10,
            'correct_answer' => null,
        ]);

        $this->actingAs($user)
            ->post(route('assessments.start', [$course, $assessment]))
            ->assertRedirect();

        $attempt = AssessmentAttempt::query()->where('user_id', $user->id)->first();

        $this->actingAs($user)
            ->post(route('assessments.attempt.submit', [$course, $assessment, $attempt]), [
                'answers' => [
                    ['question_id' => $question->id, 'answer_text' => 'Agen bukan chatbot.'],
                ],
            ])
            ->assertRedirect();

        $answer = AttemptAnswer::query()->where('question_id', $question->id)->first();
        expect($answer->proposal_status)->toBe('pending')
            ->and($answer->graded_at)->toBeNull()
            ->and($answer->proposal_score)->not->toBeNull();

        $this->actingAs($user)
            ->get(route('assessments.attempt.complete', [$course, $assessment, $attempt]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('attempt.answers.0.waiting_for_grade', true)
                ->missing('attempt.answers.0.proposal_score')
            );
    });

    it('does not propose on multiple choice', function () {
        ['admin' => $admin, 'course' => $course] = proposalCourse();
        ['user' => $user] = createEnrolledLearner($course);

        $assessment = Assessment::factory()->published()->create([
            'course_id' => $course->id,
            'user_id' => $admin->id,
            'time_limit_minutes' => null,
        ]);
        $question = Question::factory()->multipleChoice()->create([
            'assessment_id' => $assessment->id,
            'points' => 5,
        ]);
        $correct = QuestionOption::factory()->correct()->create(['question_id' => $question->id]);
        QuestionOption::factory()->incorrect()->create(['question_id' => $question->id]);

        $this->actingAs($user)->post(route('assessments.start', [$course, $assessment]));
        $attempt = AssessmentAttempt::query()->where('user_id', $user->id)->first();

        $this->actingAs($user)->post(route('assessments.attempt.submit', [$course, $assessment, $attempt]), [
            'answers' => [
                ['question_id' => $question->id, 'selected_options' => [$correct->id]],
            ],
        ]);

        $answer = AttemptAnswer::query()->where('question_id', $question->id)->first();
        expect($answer->proposal_status)->toBeNull()
            ->and((float) $answer->score)->toBe(5.0);
    });

    it('accepts a proposal as the grade and reject leaves it waiting', function () {
        ['admin' => $admin, 'course' => $course] = proposalCourse();
        ['user' => $user] = createEnrolledLearner($course);

        $assessment = Assessment::factory()->published()->create([
            'course_id' => $course->id,
            'user_id' => $admin->id,
            'time_limit_minutes' => null,
        ]);
        $question = Question::factory()->essay()->create([
            'assessment_id' => $assessment->id,
            'points' => 20,
        ]);

        $this->actingAs($user)->post(route('assessments.start', [$course, $assessment]));
        $attempt = AssessmentAttempt::query()->where('user_id', $user->id)->first();
        $this->actingAs($user)->post(route('assessments.attempt.submit', [$course, $assessment, $attempt]), [
            'answers' => [
                ['question_id' => $question->id, 'answer_text' => 'Esai tentang agen.'],
            ],
        ]);

        $answer = AttemptAnswer::query()->first();

        $this->actingAs($admin)
            ->post(route('assessments.grade.proposal.reject', [$course, $assessment, $attempt, $answer]))
            ->assertRedirect();

        $answer->refresh();
        expect($answer->proposal_status)->toBe('rejected')
            ->and($answer->graded_at)->toBeNull();

        $this->actingAs($admin)
            ->post(route('assessments.grade.proposal.repropose', [$course, $assessment, $attempt, $answer]))
            ->assertRedirect();

        $answer->refresh();
        expect($answer->proposal_status)->toBe('pending');

        $this->actingAs($admin)
            ->post(route('assessments.grade.proposal.accept', [$course, $assessment, $attempt, $answer]))
            ->assertRedirect();

        $answer->refresh();
        expect($answer->proposal_status)->toBe('accepted')
            ->and($answer->graded_at)->not->toBeNull()
            ->and($answer->score)->not->toBeNull();
    });

    it('does not write a Conversation when proposing', function () {
        ['admin' => $admin, 'course' => $course] = proposalCourse();
        ['user' => $user] = createEnrolledLearner($course);

        $assessment = Assessment::factory()->published()->create([
            'course_id' => $course->id,
            'user_id' => $admin->id,
            'time_limit_minutes' => null,
        ]);
        Question::factory()->essay()->create([
            'assessment_id' => $assessment->id,
            'points' => 10,
        ]);
        $question = $assessment->questions()->first();

        $this->actingAs($user)->post(route('assessments.start', [$course, $assessment]));
        $attempt = AssessmentAttempt::query()->where('user_id', $user->id)->first();
        $this->actingAs($user)->post(route('assessments.attempt.submit', [$course, $assessment, $attempt]), [
            'answers' => [
                ['question_id' => $question->id, 'answer_text' => 'Esai.'],
            ],
        ]);

        expect(Conversation::query()->count())->toBe(0);
    });
});
