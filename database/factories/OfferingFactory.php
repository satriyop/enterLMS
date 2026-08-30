<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Offering;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Offering>
 */
class OfferingFactory extends Factory
{
    public function definition(): array
    {
        $name = fake('id_ID')->unique()->words(2, true);

        return [
            'course_id' => Course::factory(),
            'name' => $name,
            'code' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'starts_at' => null,
            'ends_at' => null,
            'capacity' => null,
            'facilitator_id' => null,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => Offering::DEFAULT_CODE,
            'is_default' => true,
        ]);
    }

    public function withFacilitator(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'facilitator_id' => $user?->id ?? User::factory(),
        ]);
    }
}
