<?php

namespace App\Domain\QuestionBank\Services;

use App\Domain\QuestionBank\DTOs\ImportResult;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\QuestionBankItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionImportService
{
    /**
     * Import questions from bank items to an assessment.
     *
     * Questions are deep-copied (not linked) to allow independent customization.
     *
     * @param  array<int, int>  $bankItemIds
     */
    public function importToAssessment(Assessment $assessment, array $bankItemIds): ImportResult
    {
        if (empty($bankItemIds)) {
            return new ImportResult(0, 0, 0);
        }

        return DB::transaction(function () use ($assessment, $bankItemIds) {
            // Load all bank items with their options in one query
            /** @var Collection<int, QuestionBankItem> $bankItems */
            $bankItems = QuestionBankItem::with('options')
                ->whereIn('id', $bankItemIds)
                ->get()
                ->keyBy('id');

            if ($bankItems->isEmpty()) {
                return new ImportResult(0, 0, 0);
            }

            // Get current max order from assessment questions
            $currentMaxOrder = $assessment->questions()->max('order') ?? 0;
            $startingOrder = $currentMaxOrder + 1;

            $importedCount = 0;
            $totalPoints = 0;

            // Preserve the order from the input array
            foreach ($bankItemIds as $bankItemId) {
                $bankItem = $bankItems->get($bankItemId);
                if (! $bankItem) {
                    continue;
                }

                $order = $startingOrder + $importedCount;

                // Create Question copy
                $question = $this->createQuestionFromBankItem($assessment, $bankItem, $order);

                // Copy options
                $this->copyOptionsToQuestion($bankItem, $question);

                // Track stats
                $importedCount++;
                $totalPoints += $question->points;

                // Increment usage count on the source item
                $bankItem->incrementTimesUsed();
            }

            return new ImportResult($importedCount, $totalPoints, $startingOrder);
        });
    }

    /**
     * Create a Question from a QuestionBankItem.
     */
    private function createQuestionFromBankItem(
        Assessment $assessment,
        QuestionBankItem $bankItem,
        int $order
    ): Question {
        /** @var Question $question */
        $question = $assessment->questions()->create([
            'question_text' => $bankItem->question_text,
            'question_type' => $bankItem->question_type,
            'points' => $bankItem->default_points,
            'feedback' => $bankItem->feedback,
            'order' => $order,
            'correct_answer' => $bankItem->correct_answer,
            'case_sensitive' => $bankItem->case_sensitive,
            'grading_rubric' => $bankItem->grading_rubric,
            'source_question_bank_item_id' => $bankItem->id,
        ]);

        return $question;
    }

    /**
     * Copy options from bank item to question.
     */
    private function copyOptionsToQuestion(QuestionBankItem $bankItem, Question $question): void
    {
        foreach ($bankItem->options as $bankOption) {
            $question->options()->create([
                'option_text' => $bankOption->option_text,
                'is_correct' => $bankOption->is_correct,
                'feedback' => $bankOption->feedback,
                'order' => $bankOption->order,
            ]);
        }
    }

    /**
     * Export a question back to the question bank.
     */
    public function exportToBank(Question $question, int $userId): QuestionBankItem
    {
        return DB::transaction(function () use ($question, $userId) {
            $bankItem = QuestionBankItem::create([
                'user_id' => $userId,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'default_points' => $question->points,
                'feedback' => $question->feedback,
                'correct_answer' => $question->correct_answer,
                'case_sensitive' => $question->case_sensitive ?? false,
                'grading_rubric' => $question->grading_rubric,
                'visibility' => 'private',
            ]);

            // Copy options
            foreach ($question->options as $option) {
                $bankItem->options()->create([
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                    'feedback' => $option->feedback,
                    'order' => $option->order,
                ]);
            }

            return $bankItem->load('options');
        });
    }
}
