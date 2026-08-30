<?php

namespace Database\Factories;

use App\Models\ChannelIdentity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChannelIdentity>
 */
class ChannelIdentityFactory extends Factory
{
    protected $model = ChannelIdentity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->learner(),
            'channel' => ChannelIdentity::CHANNEL_WHATSAPP,
            'identifier' => '62'.fake()->unique()->numerify('8##########'),
        ];
    }

    public function whatsapp(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => ChannelIdentity::CHANNEL_WHATSAPP,
        ]);
    }

    public function telegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => ChannelIdentity::CHANNEL_TELEGRAM,
            'identifier' => (string) fake()->unique()->numerify('########'),
        ]);
    }
}
