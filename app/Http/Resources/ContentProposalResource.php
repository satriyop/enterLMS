<?php

namespace App\Http\Resources;

use App\Models\ContentProposal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContentProposal
 */
class ContentProposalResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'lesson_id' => $this->lesson_id,
            'lesson_title' => $this->whenLoaded(
                'lesson',
                fn () => $this->lesson instanceof \App\Models\Lesson ? $this->lesson->title : null,
            ),
            'instruction' => $this->instruction,
            'grounding_body' => $this->grounding_body,
            'proposed_body_text' => $this->proposed_body_text,
            'reason' => $this->reason,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
