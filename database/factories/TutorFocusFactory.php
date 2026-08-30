<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\TutorFocus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TutorFocus>
 */
class TutorFocusFactory extends Factory
{
    protected $model = TutorFocus::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->learner(),
            'skin' => TutorFocus::SKIN_WHATSAPP,
            'enrollment_id' => Enrollment::factory()->active(),
            'lesson_id' => Lesson::factory(),
        ];
    }

    public function whatsapp(): static
    {
        return $this->state(fn (array $attributes) => [
            'skin' => TutorFocus::SKIN_WHATSAPP,
        ]);
    }

    public function telegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'skin' => TutorFocus::SKIN_TELEGRAM,
        ]);
    }
}
