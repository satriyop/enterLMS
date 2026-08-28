<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationTurn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationTurn>
 */
class ConversationTurnFactory extends Factory
{
    protected $model = ConversationTurn::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'role' => ConversationTurn::ROLE_LEARNER,
            'body' => fake('id_ID')->sentence(),
        ];
    }
}
