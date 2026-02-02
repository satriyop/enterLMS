<?php

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;

beforeEach(function () {
    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->contentManager = User::factory()->create(['role' => 'content_manager']);

    $this->course = Course::factory()->published()->create([
        'user_id' => $this->contentManager->id,
    ]);

    $section = CourseSection::factory()->create(['course_id' => $this->course->id]);
    Lesson::factory()->create(['course_section_id' => $section->id]);

    Enrollment::factory()->active()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
    ]);
});

describe('Assessment server-side time limit enforcement', function () {
    it('rejects submission after time limit expires', function () {
        $assessment = Assessment::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->contentManager->id,
            'status' => 'published',
            'time_limit_minutes' => 30,
            'passing_score' => 70,
        ]);

        $question = Question::factory()->multipleChoice()->create([
            'assessment_id' => $assessment->id,
            'points' => 10,
        ]);

        $correctOption = QuestionOption::factory()->correct()->create([
            'question_id' => $question->id,
        ]);

        // Start attempt 31 minutes + 1 second ago (past time limit + 60s grace)
        $attempt = AssessmentAttempt::factory()->inProgress()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $this->learner->id,
            'started_at' => now()->subMinutes(31)->subSeconds(1),
        ]);

        $response = $this->actingAs($this->learner)
            ->post(route('assessments.attempt.submit', [$this->course, $assessment, $attempt]), [
                'answers' => [
                    [
                        'question_id' => $question->id,
                        'selected_options' => [$correctOption->id],
                        'answer_text' => null,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Attempt should still be in_progress (not submitted)
        $attempt->refresh();
        expect($attempt->status)->toBe('in_progress');
    });

    it('allows submission within time limit', function () {
        $assessment = Assessment::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->contentManager->id,
            'status' => 'published',
            'time_limit_minutes' => 30,
            'passing_score' => 70,
        ]);

        $question = Question::factory()->multipleChoice()->create([
            'assessment_id' => $assessment->id,
            'points' => 10,
        ]);

        $correctOption = QuestionOption::factory()->correct()->create([
            'question_id' => $question->id,
        ]);

        // Start attempt 20 minutes ago (within time limit)
        $attempt = AssessmentAttempt::factory()->inProgress()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $this->learner->id,
            'started_at' => now()->subMinutes(20),
        ]);

        $response = $this->actingAs($this->learner)
            ->post(route('assessments.attempt.submit', [$this->course, $assessment, $attempt]), [
                'answers' => [
                    [
                        'question_id' => $question->id,
                        'selected_options' => [$correctOption->id],
                        'answer_text' => null,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $attempt->refresh();
        expect($attempt->status)->not->toBe('in_progress');
    });

    it('allows submission within grace period', function () {
        $assessment = Assessment::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->contentManager->id,
            'status' => 'published',
            'time_limit_minutes' => 30,
            'passing_score' => 70,
        ]);

        $question = Question::factory()->multipleChoice()->create([
            'assessment_id' => $assessment->id,
            'points' => 10,
        ]);

        $correctOption = QuestionOption::factory()->correct()->create([
            'question_id' => $question->id,
        ]);

        // Start attempt 30 minutes + 30 seconds ago (past limit but within 60s grace)
        $attempt = AssessmentAttempt::factory()->inProgress()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $this->learner->id,
            'started_at' => now()->subMinutes(30)->subSeconds(30),
        ]);

        $response = $this->actingAs($this->learner)
            ->post(route('assessments.attempt.submit', [$this->course, $assessment, $attempt]), [
                'answers' => [
                    [
                        'question_id' => $question->id,
                        'selected_options' => [$correctOption->id],
                        'answer_text' => null,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    });

    it('allows submission when assessment has no time limit', function () {
        $assessment = Assessment::factory()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->contentManager->id,
            'status' => 'published',
            'time_limit_minutes' => null,
            'passing_score' => 70,
        ]);

        $question = Question::factory()->multipleChoice()->create([
            'assessment_id' => $assessment->id,
            'points' => 10,
        ]);

        $correctOption = QuestionOption::factory()->correct()->create([
            'question_id' => $question->id,
        ]);

        // Start attempt 2 hours ago — no time limit so should still work
        $attempt = AssessmentAttempt::factory()->inProgress()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $this->learner->id,
            'started_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($this->learner)
            ->post(route('assessments.attempt.submit', [$this->course, $assessment, $attempt]), [
                'answers' => [
                    [
                        'question_id' => $question->id,
                        'selected_options' => [$correctOption->id],
                        'answer_text' => null,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    });
});
