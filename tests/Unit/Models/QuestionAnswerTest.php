<?php

use App\Models\Question;

describe('Question::extractAnswerValue', function () {
    it('extracts selected options for multiple choice questions', function () {
        $question = new Question(['question_type' => 'multiple_choice']);
        $answerData = ['selected_options' => [1, 3, 5]];

        expect($question->extractAnswerValue($answerData))->toBe([1, 3, 5]);
    });

    it('returns empty array when selected_options is missing for multiple choice', function () {
        $question = new Question(['question_type' => 'multiple_choice']);
        $answerData = ['answer_text' => 'some text'];

        expect($question->extractAnswerValue($answerData))->toBe([]);
    });

    it('extracts answer text for non-multiple-choice questions', function (string $type) {
        $question = new Question(['question_type' => $type]);
        $answerData = ['answer_text' => 'The answer'];

        expect($question->extractAnswerValue($answerData))->toBe('The answer');
    })->with(['true_false', 'short_answer', 'essay', 'matching']);

    it('returns empty string when answer_text is missing for non-multiple-choice', function () {
        $question = new Question(['question_type' => 'short_answer']);
        $answerData = [];

        expect($question->extractAnswerValue($answerData))->toBe('');
    });

    it('returns empty array when selected_options is null', function () {
        $question = new Question(['question_type' => 'multiple_choice']);
        $answerData = ['selected_options' => null];

        // null coalesces to empty array via ?? []
        expect($question->extractAnswerValue($answerData))->toBe([]);
    });

    it('handles empty selected_options array', function () {
        $question = new Question(['question_type' => 'multiple_choice']);
        $answerData = ['selected_options' => []];

        expect($question->extractAnswerValue($answerData))->toBe([]);
    });
});

describe('Question::formatAnswerForStorage', function () {
    it('returns JSON-encoded selected options for multiple choice', function () {
        $question = new Question(['question_type' => 'multiple_choice']);
        $answerData = ['selected_options' => [1, 3]];

        $result = $question->formatAnswerForStorage($answerData);

        expect($result)->toBe(json_encode([1, 3]));
    });

    it('returns null for multiple choice with empty selected_options', function () {
        $question = new Question(['question_type' => 'multiple_choice']);
        $answerData = ['selected_options' => []];

        expect($question->formatAnswerForStorage($answerData))->toBeNull();
    });

    it('returns null for multiple choice with missing selected_options', function () {
        $question = new Question(['question_type' => 'multiple_choice']);
        $answerData = [];

        expect($question->formatAnswerForStorage($answerData))->toBeNull();
    });

    it('returns answer_text for non-multiple-choice questions', function (string $type) {
        $question = new Question(['question_type' => $type]);
        $answerData = ['answer_text' => 'Jawaban singkat'];

        expect($question->formatAnswerForStorage($answerData))->toBe('Jawaban singkat');
    })->with(['true_false', 'short_answer', 'essay', 'matching']);

    it('returns null when answer_text is missing', function () {
        $question = new Question(['question_type' => 'short_answer']);
        $answerData = [];

        expect($question->formatAnswerForStorage($answerData))->toBeNull();
    });

    it('handles single option in selected_options', function () {
        $question = new Question(['question_type' => 'multiple_choice']);
        $answerData = ['selected_options' => [42]];

        expect($question->formatAnswerForStorage($answerData))->toBe(json_encode([42]));
    });
});
