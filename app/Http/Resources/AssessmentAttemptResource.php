<?php

namespace App\Http\Resources;

use App\Models\AssessmentAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for assessment attempts.
 *
 * Used in: AssessmentController (show), AssessmentAttemptController
 *
 * @mixin AssessmentAttempt
 */
class AssessmentAttemptResource extends JsonResource
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
            'attempt_number' => $this->attempt_number,
            'status' => $this->status,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'percentage' => $this->percentage,
            'passed' => $this->passed,
            'feedback' => $this->feedback,
            'started_at' => $this->started_at?->toISOString(),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'graded_at' => $this->graded_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'answers' => AttemptAnswerResource::collection($this->whenLoaded('answers')),
        ];
    }
}
