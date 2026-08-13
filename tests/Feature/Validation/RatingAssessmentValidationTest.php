<?php

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\User;

describe('Store Course Rating Validation', function () {

    beforeEach(function () {
        $this->course = Course::factory()->published()->create();
        $this->user = User::factory()->create(['role' => 'learner']);
        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
    });

    it('requires rating', function () {
        $this->actingAs($this->user)
            ->post(route('courses.ratings.store', $this->course), [
                'review' => 'Great course!',
            ])
            ->assertSessionHasErrors('rating');
    });

    it('validates rating must be integer', function () {
        $this->actingAs($this->user)
            ->post(route('courses.ratings.store', $this->course), [
                'rating' => 'five',
                'review' => 'Great course!',
            ])
            ->assertSessionHasErrors('rating');
    });

    it('validates rating bounds', function (mixed $rating, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('courses.ratings.store', $this->course), [
                'rating' => $rating,
                'review' => 'Great course!',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('rating');
        } else {
            $response->assertSessionDoesntHaveErrors('rating');
        }
    })->with([
        'one (valid min)' => [1, false],
        'three (valid)' => [3, false],
        'five (valid max)' => [5, false],
        'zero' => [0, true],
        'negative' => [-1, true],
        'six' => [6, true],
    ]);

    it('validates review is nullable', function () {
        $this->actingAs($this->user)
            ->post(route('courses.ratings.store', $this->course), [
                'rating' => 5,
            ])
            ->assertSessionDoesntHaveErrors('review');
    });

    it('validates review must be string', function () {
        $this->actingAs($this->user)
            ->post(route('courses.ratings.store', $this->course), [
                'rating' => 5,
                'review' => ['array'],
            ])
            ->assertSessionHasErrors('review');
    });

    it('validates review max length', function (string $review, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('courses.ratings.store', $this->course), [
                'rating' => 5,
                'review' => $review,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('review');
        } else {
            $response->assertSessionDoesntHaveErrors('review');
        }
    })->with([
        'at max (1000)' => [str_repeat('a', 1000), false],
        'exceeds max (1001)' => [str_repeat('a', 1001), true],
    ]);

    it('accepts valid rating data', function () {
        $this->actingAs($this->user)
            ->post(route('courses.ratings.store', $this->course), [
                'rating' => 5,
                'review' => 'Great course!',
            ])
            ->assertSessionDoesntHaveErrors();
    });

});

describe('Submit Assessment Answers Validation', function () {

    beforeEach(function () {
        $this->course = Course::factory()->published()->create();
        $this->user = User::factory()->create(['role' => 'learner']);
        $this->enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
        $this->assessment = Assessment::factory()->published()->create([
            'course_id' => $this->course->id,
        ]);
        $this->question = Question::factory()->create([
            'assessment_id' => $this->assessment->id,
        ]);
        $this->attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->user->id,
            'status' => 'in_progress',
        ]);
    });

    it('requires answers', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [])
            ->assertSessionHasErrors('answers');
    });

    it('validates answers must be array', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [
                'answers' => 'not-an-array',
            ])
            ->assertSessionHasErrors('answers');
    });

    it('validates question_id is required in answers', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [
                'answers' => [
                    [
                        'answer_text' => 'Some answer',
                    ],
                ],
            ])
            ->assertSessionHasErrors('answers.0.question_id');
    });

    it('validates question_id must be integer', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [
                'answers' => [
                    [
                        'question_id' => 'not-an-int',
                        'answer_text' => 'Some answer',
                    ],
                ],
            ])
            ->assertSessionHasErrors('answers.0.question_id');
    });

    it('validates question_id must exist in questions table', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [
                'answers' => [
                    [
                        'question_id' => 99999,
                        'answer_text' => 'Some answer',
                    ],
                ],
            ])
            ->assertSessionHasErrors('answers.0.question_id');
    });

    it('validates question_id must be scoped to assessment', function () {
        $otherAssessment = Assessment::factory()->published()->create([
            'course_id' => $this->course->id,
        ]);
        $otherQuestion = Question::factory()->create([
            'assessment_id' => $otherAssessment->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [
                'answers' => [
                    [
                        'question_id' => $otherQuestion->id,
                        'answer_text' => 'Some answer',
                    ],
                ],
            ])
            ->assertSessionHasErrors('answers.0.question_id');
    });

    it('validates answer_text is nullable', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [
                'answers' => [
                    [
                        'question_id' => $this->question->id,
                    ],
                ],
            ])
            ->assertSessionDoesntHaveErrors('answers.0.answer_text');
    });

    it('validates answer_text must be string', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [
                'answers' => [
                    [
                        'question_id' => $this->question->id,
                        'answer_text' => ['array'],
                    ],
                ],
            ])
            ->assertSessionHasErrors('answers.0.answer_text');
    });

    it('validates selected_options is nullable', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [
                'answers' => [
                    [
                        'question_id' => $this->question->id,
                        'answer_text' => 'Some answer',
                    ],
                ],
            ])
            ->assertSessionDoesntHaveErrors('answers.0.selected_options');
    });

    it('validates selected_options must be array', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [
                'answers' => [
                    [
                        'question_id' => $this->question->id,
                        'selected_options' => 'not-an-array',
                    ],
                ],
            ])
            ->assertSessionHasErrors('answers.0.selected_options');
    });

    it('validates selected_options values must be integers', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [
                'answers' => [
                    [
                        'question_id' => $this->question->id,
                        'selected_options' => ['string', 'values'],
                    ],
                ],
            ])
            ->assertSessionHasErrors(['answers.0.selected_options.0', 'answers.0.selected_options.1']);
    });

    it('accepts valid answer data', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.attempt.submit', [$this->course, $this->assessment, $this->attempt]), [
                'answers' => [
                    [
                        'question_id' => $this->question->id,
                        'answer_text' => 'My answer',
                        'selected_options' => [1, 2, 3],
                    ],
                ],
            ])
            ->assertSessionDoesntHaveErrors();
    });

});

describe('Bulk Grade Answers Validation', function () {

    beforeEach(function () {
        $this->user = User::factory()->create(['role' => 'lms_admin']);
        $this->course = Course::factory()->published()->create(['user_id' => $this->user->id]);
        $this->learner = User::factory()->create(['role' => 'learner']);

        Enrollment::factory()->create([
            'user_id' => $this->learner->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $this->assessment = Assessment::factory()->published()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->user->id,
        ]);

        $this->question = Question::factory()->create([
            'assessment_id' => $this->assessment->id,
        ]);

        $this->attempt = AssessmentAttempt::factory()->submitted()->create([
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->learner->id,
        ]);

        $this->answer = AttemptAnswer::factory()->create([
            'assessment_attempt_id' => $this->attempt->id,
            'question_id' => $this->question->id,
        ]);
    });

    it('requires grades', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [])
            ->assertSessionHasErrors('grades');
    });

    it('validates grades must be array', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => 'not-an-array',
            ])
            ->assertSessionHasErrors('grades');
    });

    it('validates answer_id is required in grades', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => [
                    [
                        'score' => 10,
                    ],
                ],
            ])
            ->assertSessionHasErrors('grades.0.answer_id');
    });

    it('validates answer_id must be integer', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => [
                    [
                        'answer_id' => 'not-an-int',
                        'score' => 10,
                    ],
                ],
            ])
            ->assertSessionHasErrors('grades.0.answer_id');
    });

    it('validates answer_id must exist in attempt_answers table', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => [
                    [
                        'answer_id' => 99999,
                        'score' => 10,
                    ],
                ],
            ])
            ->assertSessionHasErrors('grades.0.answer_id');
    });

    it('validates answer_id must be scoped to attempt', function () {
        $otherAttempt = AssessmentAttempt::factory()->submitted()->create([
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->learner->id,
        ]);

        $otherAnswer = AttemptAnswer::factory()->create([
            'assessment_attempt_id' => $otherAttempt->id,
            'question_id' => $this->question->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => [
                    [
                        'answer_id' => $otherAnswer->id,
                        'score' => 10,
                    ],
                ],
            ])
            ->assertSessionHasErrors('grades.0.answer_id');
    });

    it('validates score is required', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => [
                    [
                        'answer_id' => $this->answer->id,
                    ],
                ],
            ])
            ->assertSessionHasErrors('grades.0.score');
    });

    it('validates score must be numeric', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => [
                    [
                        'answer_id' => $this->answer->id,
                        'score' => 'not-numeric',
                    ],
                ],
            ])
            ->assertSessionHasErrors('grades.0.score');
    });

    it('validates score min value', function (mixed $score, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => [
                    [
                        'answer_id' => $this->answer->id,
                        'score' => $score,
                    ],
                ],
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('grades.0.score');
        } else {
            $response->assertSessionDoesntHaveErrors('grades.0.score');
        }
    })->with([
        'zero (valid min)' => [0, false],
        'ten (valid)' => [10, false],
        'hundred (valid)' => [100, false],
        'negative' => [-1, true],
    ]);

    it('validates feedback is nullable', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => [
                    [
                        'answer_id' => $this->answer->id,
                        'score' => 10,
                    ],
                ],
            ])
            ->assertSessionDoesntHaveErrors('grades.0.feedback');
    });

    it('validates feedback must be string', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => [
                    [
                        'answer_id' => $this->answer->id,
                        'score' => 10,
                        'feedback' => ['array'],
                    ],
                ],
            ])
            ->assertSessionHasErrors('grades.0.feedback');
    });

    it('validates feedback max length', function (string $feedback, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => [
                    [
                        'answer_id' => $this->answer->id,
                        'score' => 10,
                        'feedback' => $feedback,
                    ],
                ],
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('grades.0.feedback');
        } else {
            $response->assertSessionDoesntHaveErrors('grades.0.feedback');
        }
    })->with([
        'at max (1000)' => [str_repeat('a', 1000), false],
        'exceeds max (1001)' => [str_repeat('a', 1001), true],
    ]);

    it('accepts valid grade data', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.grade.submit', [$this->course, $this->assessment, $this->attempt]), [
                'grades' => [
                    [
                        'answer_id' => $this->answer->id,
                        'score' => 10,
                        'feedback' => 'Great answer!',
                    ],
                ],
            ])
            ->assertSessionDoesntHaveErrors();
    });

});
