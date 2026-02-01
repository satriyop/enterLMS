<?php

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for questions.
 *
 * Used in: AssessmentResource, QuestionController
 *
 * @mixin Question
 */
class QuestionResource extends JsonResource
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
            'question_text' => $this->question_text,
            'question_type' => $this->question_type,
            'points' => $this->points,
            'order' => $this->order,
            'feedback' => $this->feedback,
            'options' => QuestionOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
