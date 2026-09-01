<?php

namespace App\Http\Resources\Course;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A Conversation as an LMS Admin or Facilitator reads it.
 *
 * Deliberately not ConversationResource: that one exists to let the Learner
 * *write*, so it carries `can_post`. This is a record being read by someone
 * who is not a party to it and may never add a turn.
 *
 * @mixin Conversation
 */
class ConversationTranscriptResource extends JsonResource
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
            'turns' => $this->turns->map(fn ($turn) => [
                'id' => $turn->id,
                'role' => $turn->role,
                'body' => $turn->body,
                'created_at' => $turn->created_at?->toISOString(),
            ])->values()->all(),
        ];
    }
}
