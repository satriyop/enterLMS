<?php

namespace App\Http\Resources\Course;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row in the Conversation review list.
 *
 * Carries no turn bodies beyond the opening question -- the list answers
 * "who asked about what, and when", and the transcript answers the rest.
 *
 * @mixin Conversation
 */
class ConversationSummaryResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $enrollment = $this->enrollment;

        return [
            'id' => $this->id,
            'turns_count' => (int) $this->turns_count,
            // Subquery aliases from the review query, not columns every
            // Conversation carries -- hence getAttribute rather than a property.
            'last_turn_at' => $this->getAttribute('last_turn_at'),
            'opening_question' => $this->getAttribute('opening_question'),
            'lesson' => [
                'id' => $this->lesson->id,
                'title' => $this->lesson->title,
            ],
            'learner' => [
                'id' => $enrollment->user->id,
                'name' => $enrollment->user->name,
                'email' => $enrollment->user->email,
            ],
            'offering' => $enrollment->offering ? [
                'id' => $enrollment->offering->id,
                'name' => $enrollment->offering->name,
            ] : null,
            'enrollment_status' => $enrollment->status->getValue(),
        ];
    }
}
