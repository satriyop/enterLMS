<?php

namespace Database\Factories;

use App\Models\QuestionBankItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionBankItem>
 */
class QuestionBankItemFactory extends Factory
{
    protected $model = QuestionBankItem::class;

    public function definition(): array
    {
        $questionTypes = ['multiple_choice', 'true_false', 'short_answer', 'essay'];
        $questionType = $this->faker->randomElement($questionTypes);

        return [
            'user_id' => User::factory(),
            'question_text' => $this->faker->sentence(8),
            'question_type' => $questionType,
            'default_points' => $this->faker->numberBetween(1, 5),
            'feedback' => $this->faker->optional()->paragraph,
            'correct_answer' => null,
            'case_sensitive' => false,
            'grading_rubric' => null,
            'difficulty_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced', null]),
            'visibility' => 'private',
            'times_used' => 0,
        ];
    }

    // =========================================================================
    // Question Type States
    // =========================================================================

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'multiple_choice',
        ]);
    }

    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'true_false',
            'correct_answer' => $this->faker->randomElement(['true', 'false']),
        ]);
    }

    public function shortAnswer(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'short_answer',
            'correct_answer' => $this->faker->word,
            'case_sensitive' => $this->faker->boolean(20),
        ]);
    }

    public function essay(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'essay',
            'grading_rubric' => $this->faker->paragraph,
        ]);
    }

    public function matching(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'matching',
        ]);
    }

    public function fileUpload(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'file_upload',
            'grading_rubric' => $this->faker->paragraph,
        ]);
    }

    // =========================================================================
    // Visibility States
    // =========================================================================

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
        ]);
    }

    public function department(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'department',
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
        ]);
    }

    // =========================================================================
    // Difficulty States
    // =========================================================================

    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'beginner',
        ]);
    }

    public function intermediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'intermediate',
        ]);
    }

    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'advanced',
        ]);
    }

    // =========================================================================
    // Usage States
    // =========================================================================

    public function used(int $times = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'times_used' => $times,
        ]);
    }

    public function withOptions(int $count = 4): static
    {
        return $this->multipleChoice()->afterCreating(function (QuestionBankItem $item) use ($count) {
            $correctIndex = $this->faker->numberBetween(0, $count - 1);

            for ($i = 0; $i < $count; $i++) {
                $item->options()->create([
                    'option_text' => 'Opsi '.chr(65 + $i),
                    'is_correct' => $i === $correctIndex,
                    'feedback' => null,
                    'order' => $i,
                ]);
            }
        });
    }
}
