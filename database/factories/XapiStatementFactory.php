<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\XapiStatement>
 */
class XapiStatementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'statement_id' => Str::uuid()->toString(),
            'actor_id' => User::factory(),
            'actor_mbox' => fn (array $attrs) => 'mailto:'.fake()->safeEmail(),
            'actor_name' => fake('id_ID')->name(),
            'verb_id' => 'http://adlnet.gov/expapi/verbs/experienced',
            'verb_display' => 'experienced',
            'object_type' => 'Activity',
            'object_id' => 'http://enterlms.test/activities/lesson/'.fake()->numberBetween(1, 100),
            'object_name' => fake('id_ID')->sentence(4),
            'source' => 'native',
            'timestamp' => now(),
            'stored' => now(),
        ];
    }

    /**
     * A "completed" statement.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'verb_id' => 'http://adlnet.gov/expapi/verbs/completed',
            'verb_display' => 'completed',
            'result_completion' => true,
        ]);
    }

    /**
     * A "scored" statement with result data.
     */
    public function scored(): static
    {
        return $this->state(fn (array $attributes) => [
            'verb_id' => 'http://adlnet.gov/expapi/verbs/scored',
            'verb_display' => 'scored',
            'result_score_raw' => fake()->numberBetween(50, 100),
            'result_score_min' => 0,
            'result_score_max' => 100,
            'result_score_scaled' => fake()->randomFloat(4, 0.5, 1.0),
            'result_success' => true,
        ]);
    }

    /**
     * A SCORM-sourced statement.
     */
    public function fromScorm(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'scorm',
        ]);
    }
}
