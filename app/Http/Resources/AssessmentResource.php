<?php

namespace App\Http\Resources;

use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for assessments.
 *
 * Used in: AssessmentController (index, show, edit)
 *
 * @mixin Assessment
 */
class AssessmentResource extends JsonResource
{
    /**
     * Disable wrapping for Inertia compatibility.
     */
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
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'time_limit_minutes' => $this->time_limit_minutes,
            'passing_score' => $this->passing_score,
            'max_attempts' => $this->max_attempts,
            'shuffle_questions' => $this->shuffle_questions,
            'show_correct_answers' => $this->show_correct_answers,
            'allow_review' => $this->allow_review,
            'is_required' => $this->is_required,
            'questions_count' => $this->whenCounted('questions'),
            'attempts_count' => $this->whenCounted('attempts'),
            'questions_sum_points' => $this->when(
                isset($this->attributes['questions_sum_points']),
                fn () => (int) ($this->attributes['questions_sum_points'] ?? 0)
            ),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'publishedBy' => $this->whenLoaded('publishedBy', fn () => [
                'id' => $this->publishedBy->id,
                'name' => $this->publishedBy->name,
            ]),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
