<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $enrollment = $this->enrollment;

        $canPost = $user !== null
            && $enrollment->user_id === $user->id
            && ($enrollment->isActive() || $enrollment->isCompleted());

        return [
            'id' => $this->id,
            'can_post' => $canPost,
            'turns' => $this->turns->map(fn ($turn) => [
                'id' => $turn->id,
                'role' => $turn->role,
                'body' => $turn->body,
                'created_at' => $turn->created_at?->toISOString(),
            ])->values()->all(),
        ];
    }
}
