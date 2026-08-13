<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'lms_admin']);
    $this->course = Course::factory()->draft()->create(['user_id' => $this->user->id]);
    $this->assessment = Assessment::factory()->draft()->create([
        'course_id' => $this->course->id,
        'user_id' => $this->user->id,
    ]);
    $this->route = route('assessments.questions.bulkUpdate', [$this->course, $this->assessment]);
});

describe('Question Bulk Update Validation', function () {

    it('requires questions array', function () {
        $this->actingAs($this->user)
            ->put($this->route, [])
            ->assertSessionHasErrors('questions');
    });

    it('requires question_text', function () {
        $this->actingAs($this->user)
            ->put($this->route, [
                'questions' => [[
                    'question_text' => '',
                    'question_type' => 'short_answer',
                    'points' => 1,
                ]],
            ])
            ->assertSessionHasErrors('questions.0.question_text');
    });

    it('requires question_type', function () {
        $this->actingAs($this->user)
            ->put($this->route, [
                'questions' => [[
                    'question_text' => 'Apa itu Laravel?',
                    'points' => 1,
                ]],
            ])
            ->assertSessionHasErrors('questions.0.question_type');
    });

    it('validates question_type enum', function (string $type, bool $shouldFail) {
        $payload = [
            'questions' => [[
                'question_text' => 'Test question',
                'question_type' => $type,
                'points' => 1,
            ]],
        ];

        // Add options for types that need them
        if (in_array($type, ['multiple_choice', 'true_false'])) {
            $payload['questions'][0]['options'] = [
                ['option_text' => 'Option A', 'is_correct' => true],
                ['option_text' => 'Option B', 'is_correct' => false],
            ];
        }

        $response = $this->actingAs($this->user)
            ->put($this->route, $payload);

        if ($shouldFail) {
            $response->assertSessionHasErrors('questions.0.question_type');
        } else {
            $response->assertSessionDoesntHaveErrors('questions.0.question_type');
        }
    })->with([
        'multiple_choice' => ['multiple_choice', false],
        'true_false' => ['true_false', false],
        'matching' => ['matching', false],
        'short_answer' => ['short_answer', false],
        'essay' => ['essay', false],
        'file_upload' => ['file_upload', false],
        'invalid type' => ['checkbox', true],
    ]);

    it('requires points', function () {
        $this->actingAs($this->user)
            ->put($this->route, [
                'questions' => [[
                    'question_text' => 'Test question',
                    'question_type' => 'short_answer',
                ]],
            ])
            ->assertSessionHasErrors('questions.0.points');
    });

    it('validates points must be at least 1', function (mixed $points, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->put($this->route, [
                'questions' => [[
                    'question_text' => 'Test question',
                    'question_type' => 'short_answer',
                    'points' => $points,
                ]],
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('questions.0.points');
        } else {
            $response->assertSessionDoesntHaveErrors('questions.0.points');
        }
    })->with([
        'one (valid min)' => [1, false],
        'ten (valid)' => [10, false],
        'zero (invalid)' => [0, true],
        'negative (invalid)' => [-1, true],
    ]);

    it('multiple_choice requires at least 2 options', function () {
        $this->actingAs($this->user)
            ->put($this->route, [
                'questions' => [[
                    'question_text' => 'Test question',
                    'question_type' => 'multiple_choice',
                    'points' => 1,
                    'options' => [
                        ['option_text' => 'Only one', 'is_correct' => true],
                    ],
                ]],
            ])
            ->assertSessionHasErrors('questions.0.options');
    });

    it('does not require options for essay', function () {
        $this->actingAs($this->user)
            ->put($this->route, [
                'questions' => [[
                    'question_text' => 'Jelaskan konsep OOP.',
                    'question_type' => 'essay',
                    'points' => 5,
                ]],
            ])
            ->assertSessionDoesntHaveErrors('questions.0.options');
    });

    it('requires option_text within options', function () {
        $this->actingAs($this->user)
            ->put($this->route, [
                'questions' => [[
                    'question_text' => 'Test question',
                    'question_type' => 'multiple_choice',
                    'points' => 1,
                    'options' => [
                        ['option_text' => '', 'is_correct' => true],
                        ['option_text' => 'Option B', 'is_correct' => false],
                    ],
                ]],
            ])
            ->assertSessionHasErrors('questions.0.options.0.option_text');
    });

    it('requires is_correct within options', function () {
        $this->actingAs($this->user)
            ->put($this->route, [
                'questions' => [[
                    'question_text' => 'Test question',
                    'question_type' => 'multiple_choice',
                    'points' => 1,
                    'options' => [
                        ['option_text' => 'Option A'],
                        ['option_text' => 'Option B', 'is_correct' => false],
                    ],
                ]],
            ])
            ->assertSessionHasErrors('questions.0.options.0.is_correct');
    });

});
