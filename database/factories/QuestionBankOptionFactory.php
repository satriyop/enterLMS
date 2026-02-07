<?php

namespace Database\Factories;

use App\Models\QuestionBankItem;
use App\Models\QuestionBankOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionBankOption>
 */
class QuestionBankOptionFactory extends Factory
{
    protected $model = QuestionBankOption::class;

    public function definition(): array
    {
        static $orderCounters = [];

        return [
            'question_bank_item_id' => QuestionBankItem::factory(),
            'option_text' => $this->faker->sentence(3),
            'is_correct' => false,
            'feedback' => $this->faker->optional(0.3)->sentence,
            'order' => function (array $attributes) use (&$orderCounters) {
                $itemId = $attributes['question_bank_item_id'];
                $orderCounters[$itemId] = ($orderCounters[$itemId] ?? -1) + 1;

                return $orderCounters[$itemId];
            },
        ];
    }

    public function correct(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_correct' => true,
        ]);
    }

    public function withFeedback(): static
    {
        return $this->state(fn (array $attributes) => [
            'feedback' => $this->faker->sentence,
        ]);
    }
}
