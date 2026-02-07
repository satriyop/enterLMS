<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\ScormPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScormScoTracking>
 */
class ScormScoTrackingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'lesson_id' => Lesson::factory(),
            'scorm_package_id' => ScormPackage::factory(),
            'lesson_status' => 'not attempted',
            'completion_status' => 'not attempted',
            'success_status' => 'unknown',
            'launched_at' => now(),
        ];
    }

    /**
     * A completed SCORM 1.2 tracking record.
     */
    public function completedScorm12(): static
    {
        return $this->state(fn (array $attributes) => [
            'lesson_status' => 'completed',
            'score_raw' => fake()->numberBetween(70, 100),
            'score_min' => 0,
            'score_max' => 100,
            'total_time' => '00:15:30',
            'finished_at' => now(),
        ]);
    }

    /**
     * A completed SCORM 2004 tracking record.
     */
    public function completedScorm2004(): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_status' => 'completed',
            'success_status' => 'passed',
            'score_raw' => fake()->numberBetween(70, 100),
            'score_min' => 0,
            'score_max' => 100,
            'score_scaled' => fake()->randomFloat(4, 0.7, 1.0),
            'total_time' => 'PT15M30S',
            'finished_at' => now(),
        ]);
    }

    /**
     * An in-progress tracking record with suspend data.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'lesson_status' => 'incomplete',
            'completion_status' => 'incomplete',
            'exit_type' => 'suspend',
            'entry' => 'resume',
            'suspend_data' => json_encode(['page' => 3, 'score' => 45]),
            'location' => 'page-3',
        ]);
    }
}
