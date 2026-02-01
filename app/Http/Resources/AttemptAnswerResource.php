<?php

namespace App\Http\Resources;

use App\Models\AttemptAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for attempt answers.
 *
 * Used in: AssessmentAttemptResource
 *
 * @mixin AttemptAnswer
 */
class AttemptAnswerResource extends JsonResource
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
        // Determine if this is an in-progress attempt by checking route
        $routeAttempt = $request->route('attempt');
        $isInProgress = $routeAttempt && $routeAttempt->status === 'in_progress';

        // Show sensitive fields only when NOT in progress
        $showSensitiveFields = ! $isInProgress;

        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'answer_text' => $this->answer_text,
            'file_path' => $this->file_path,
            'is_correct' => $this->when($showSensitiveFields, $this->is_correct),
            'score' => $this->when($showSensitiveFields, $this->score),
            'feedback' => $this->when($showSensitiveFields, $this->feedback),
            'question' => new QuestionResource($this->whenLoaded('question')),
        ];
    }
}
