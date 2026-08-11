<?php

namespace App\Http\Resources\QuestionBank;

use App\Models\QuestionBankItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuestionBankItem
 */
class QuestionBankItemResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'question_text' => $this->question_text,
            'question_type' => $this->question_type,
            'question_type_label' => $this->getQuestionTypeLabel(),
            'default_points' => $this->default_points,
            'feedback' => $this->feedback,
            'correct_answer' => $this->correct_answer,
            'case_sensitive' => $this->case_sensitive,
            'grading_rubric' => $this->grading_rubric,
            'difficulty_level' => $this->difficulty_level,
            'difficulty_label' => $this->getDifficultyLabel(),
            'visibility' => $this->visibility,
            'visibility_label' => $this->getVisibilityLabel(),
            'times_used' => $this->times_used,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'option_text' => $option->option_text,
                'is_correct' => $option->is_correct,
                'feedback' => $option->feedback,
                'order' => $option->order,
            ])),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])),

            // Counts
            'options_count' => $this->whenCounted('options'),
            'derived_questions_count' => $this->whenCounted('derivedQuestions'),
        ];
    }
}
