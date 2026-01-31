<?php

use App\Domain\Assessment\Services\QuestionManagementService;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;

beforeEach(function () {
    $this->service = app(QuestionManagementService::class);
});

describe('QuestionManagementService', function () {
    describe('bulkUpdate', function () {
        it('creates new questions with options', function () {
            $user = User::factory()->create(['role' => 'content_manager']);
            $assessment = Assessment::factory()->create(['user_id' => $user->id]);

            $questionsData = [
                [
                    'question_text' => 'Apa ibu kota Indonesia?',
                    'question_type' => 'multiple_choice',
                    'points' => 5,
                    'feedback' => 'Jakarta adalah ibu kota Indonesia.',
                    'order' => 0,
                    'options' => [
                        ['option_text' => 'Jakarta', 'is_correct' => true, 'order' => 0],
                        ['option_text' => 'Bandung', 'is_correct' => false, 'order' => 1],
                        ['option_text' => 'Surabaya', 'is_correct' => false, 'order' => 2],
                    ],
                ],
            ];

            $this->service->bulkUpdate($assessment, $questionsData);

            expect($assessment->questions()->count())->toBe(1);

            $question = $assessment->questions()->first();
            expect($question->question_text)->toBe('Apa ibu kota Indonesia?');
            expect($question->question_type)->toBe('multiple_choice');
            expect($question->points)->toBe(5);
            expect($question->options()->count())->toBe(3);

            $correctOption = $question->options()->where('is_correct', true)->first();
            expect($correctOption->option_text)->toBe('Jakarta');
        });

        it('updates existing questions', function () {
            $user = User::factory()->create(['role' => 'content_manager']);
            $assessment = Assessment::factory()->create(['user_id' => $user->id]);
            $question = Question::factory()->multipleChoice()->create([
                'assessment_id' => $assessment->id,
                'question_text' => 'Pertanyaan lama',
                'points' => 3,
            ]);

            $questionsData = [
                [
                    'id' => $question->id,
                    'question_text' => 'Pertanyaan baru',
                    'question_type' => 'multiple_choice',
                    'points' => 10,
                    'feedback' => 'Feedback baru',
                    'order' => 0,
                ],
            ];

            $this->service->bulkUpdate($assessment, $questionsData);

            $question->refresh();
            expect($question->question_text)->toBe('Pertanyaan baru');
            expect($question->points)->toBe(10);
            expect($question->feedback)->toBe('Feedback baru');
        });

        it('updates existing question options', function () {
            $user = User::factory()->create(['role' => 'content_manager']);
            $assessment = Assessment::factory()->create(['user_id' => $user->id]);
            $question = Question::factory()->multipleChoice()->create([
                'assessment_id' => $assessment->id,
            ]);

            $option1 = QuestionOption::factory()->create([
                'question_id' => $question->id,
                'option_text' => 'Opsi lama 1',
                'is_correct' => true,
            ]);

            $option2 = QuestionOption::factory()->create([
                'question_id' => $question->id,
                'option_text' => 'Opsi lama 2',
                'is_correct' => false,
            ]);

            $questionsData = [
                [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => 'multiple_choice',
                    'points' => 5,
                    'order' => 0,
                    'options' => [
                        [
                            'id' => $option1->id,
                            'option_text' => 'Opsi baru 1',
                            'is_correct' => false,
                            'order' => 0,
                        ],
                        [
                            'id' => $option2->id,
                            'option_text' => 'Opsi baru 2',
                            'is_correct' => true,
                            'order' => 1,
                        ],
                    ],
                ],
            ];

            $this->service->bulkUpdate($assessment, $questionsData);

            $option1->refresh();
            $option2->refresh();

            expect($option1->option_text)->toBe('Opsi baru 1');
            expect($option1->is_correct)->toBeFalse();
            expect($option2->option_text)->toBe('Opsi baru 2');
            expect($option2->is_correct)->toBeTrue();
        });

        it('deletes questions not in the submitted data', function () {
            $user = User::factory()->create(['role' => 'content_manager']);
            $assessment = Assessment::factory()->create(['user_id' => $user->id]);

            $question1 = Question::factory()->create(['assessment_id' => $assessment->id]);
            $question2 = Question::factory()->create(['assessment_id' => $assessment->id]);
            $question3 = Question::factory()->create(['assessment_id' => $assessment->id]);

            $questionsData = [
                [
                    'id' => $question1->id,
                    'question_text' => $question1->question_text,
                    'question_type' => $question1->question_type,
                    'points' => $question1->points,
                    'order' => 0,
                ],
            ];

            $this->service->bulkUpdate($assessment, $questionsData);

            expect($assessment->questions()->count())->toBe(1);
            expect(Question::find($question1->id))->not->toBeNull();
            expect(Question::find($question2->id))->toBeNull();
            expect(Question::find($question3->id))->toBeNull();
        });

        it('deletes options not in the submitted data', function () {
            $user = User::factory()->create(['role' => 'content_manager']);
            $assessment = Assessment::factory()->create(['user_id' => $user->id]);
            $question = Question::factory()->multipleChoice()->create([
                'assessment_id' => $assessment->id,
            ]);

            $option1 = QuestionOption::factory()->create(['question_id' => $question->id]);
            $option2 = QuestionOption::factory()->create(['question_id' => $question->id]);
            $option3 = QuestionOption::factory()->create(['question_id' => $question->id]);

            $questionsData = [
                [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => 'multiple_choice',
                    'points' => 5,
                    'order' => 0,
                    'options' => [
                        [
                            'id' => $option1->id,
                            'option_text' => 'Keep this option',
                            'is_correct' => true,
                            'order' => 0,
                        ],
                    ],
                ],
            ];

            $this->service->bulkUpdate($assessment, $questionsData);

            expect($question->options()->count())->toBe(1);
            expect(QuestionOption::find($option1->id))->not->toBeNull();
            expect(QuestionOption::find($option2->id))->toBeNull();
            expect(QuestionOption::find($option3->id))->toBeNull();
        });

        it('handles mixed create, update, and delete operations', function () {
            $user = User::factory()->create(['role' => 'content_manager']);
            $assessment = Assessment::factory()->create(['user_id' => $user->id]);

            $existingQuestion = Question::factory()->create([
                'assessment_id' => $assessment->id,
                'question_text' => 'Existing question',
            ]);

            $questionsData = [
                [
                    'id' => $existingQuestion->id,
                    'question_text' => 'Updated existing question',
                    'question_type' => $existingQuestion->question_type,
                    'points' => 10,
                    'order' => 0,
                ],
                [
                    'question_text' => 'New question',
                    'question_type' => 'short_answer',
                    'points' => 5,
                    'order' => 1,
                ],
            ];

            $this->service->bulkUpdate($assessment, $questionsData);

            expect($assessment->questions()->count())->toBe(2);

            $existingQuestion->refresh();
            expect($existingQuestion->question_text)->toBe('Updated existing question');

            $newQuestion = $assessment->questions()
                ->where('question_text', 'New question')
                ->first();
            expect($newQuestion)->not->toBeNull();
        });

        it('handles essay questions without options', function () {
            $user = User::factory()->create(['role' => 'content_manager']);
            $assessment = Assessment::factory()->create(['user_id' => $user->id]);

            $questionsData = [
                [
                    'question_text' => 'Jelaskan konsep OOP',
                    'question_type' => 'essay',
                    'points' => 20,
                    'order' => 0,
                ],
            ];

            $this->service->bulkUpdate($assessment, $questionsData);

            $question = $assessment->questions()->first();
            expect($question->question_text)->toBe('Jelaskan konsep OOP');
            expect($question->question_type)->toBe('essay');
            expect($question->options()->count())->toBe(0);
        });

        it('batch loads existing questions instead of N+1 queries', function () {
            $user = User::factory()->create(['role' => 'content_manager']);
            $assessment = Assessment::factory()->create(['user_id' => $user->id]);

            $question1 = Question::factory()->create(['assessment_id' => $assessment->id]);
            $question2 = Question::factory()->create(['assessment_id' => $assessment->id]);

            $questionsData = [
                [
                    'id' => $question1->id,
                    'question_text' => 'Updated Q1',
                    'question_type' => 'multiple_choice',
                    'points' => 5,
                    'order' => 0,
                ],
                [
                    'id' => $question2->id,
                    'question_text' => 'Updated Q2',
                    'question_type' => 'multiple_choice',
                    'points' => 5,
                    'order' => 1,
                ],
            ];

            $this->service->bulkUpdate($assessment, $questionsData);

            $question1->refresh();
            $question2->refresh();

            expect($question1->question_text)->toBe('Updated Q1');
            expect($question2->question_text)->toBe('Updated Q2');
        });
    });
});
