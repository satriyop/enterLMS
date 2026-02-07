<?php

namespace App\Domain\QuestionBank\Services;

use App\Domain\QuestionBank\DTOs\QuestionBankItemData;
use App\Models\QuestionBankItem;

class QuestionBankService
{
    /**
     * Create a new question bank item.
     */
    public function create(QuestionBankItemData $data): QuestionBankItem
    {
        $item = QuestionBankItem::create($data->toModelData());

        if (! empty($data->options)) {
            foreach ($data->options as $index => $optionData) {
                $item->options()->create([
                    'option_text' => $optionData['option_text'],
                    'is_correct' => $optionData['is_correct'] ?? false,
                    'feedback' => $optionData['feedback'] ?? null,
                    'order' => $optionData['order'] ?? $index,
                ]);
            }
        }

        if (! empty($data->tag_ids)) {
            $item->tags()->sync($data->tag_ids);
        }

        return $item->load(['options', 'tags']);
    }

    /**
     * Update an existing question bank item.
     */
    public function update(QuestionBankItem $item, QuestionBankItemData $data): QuestionBankItem
    {
        $item->update($data->toModelData());

        if (! empty($data->options)) {
            $item->syncOptions($data->options);
        }

        if (isset($data->tag_ids)) {
            $item->tags()->sync($data->tag_ids);
        }

        return $item->fresh(['options', 'tags']);
    }

    /**
     * Delete a question bank item.
     */
    public function delete(QuestionBankItem $item): bool
    {
        return $item->delete();
    }

    /**
     * Duplicate a question bank item for the given user.
     */
    public function duplicate(QuestionBankItem $source, int $userId): QuestionBankItem
    {
        // Ensure options and tags are loaded
        $source->loadMissing(['options', 'tags']);

        $duplicate = $source->replicate();
        $duplicate->user_id = $userId;
        $duplicate->visibility = 'private';
        $duplicate->times_used = 0;
        $duplicate->question_text = $source->question_text.' (Salinan)';
        $duplicate->save();

        // Copy options
        foreach ($source->options as $option) {
            $duplicate->options()->create([
                'option_text' => $option->option_text,
                'is_correct' => $option->is_correct,
                'feedback' => $option->feedback,
                'order' => $option->order,
            ]);
        }

        // Copy tags
        $duplicate->tags()->sync($source->tags->pluck('id'));

        return $duplicate->load(['options', 'tags']);
    }
}
