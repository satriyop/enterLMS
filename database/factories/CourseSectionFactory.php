<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseSection>
 */
class CourseSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Pendahuluan',
            'Pengenalan Konsep Dasar',
            'Memulai Praktik',
            'Pendalaman Materi',
            'Studi Kasus',
            'Proyek Akhir',
            'Evaluasi dan Penutup',
            'Bonus: Tips dan Trik',
        ];

        static $orderCounters = [];

        return [
            'course_id' => Course::factory(),
            'title' => fake()->randomElement($titles),
            'description' => fake('id_ID')->sentence(),
            'order' => function (array $attributes) use (&$orderCounters) {
                $courseId = $attributes['course_id'];
                $orderCounters[$courseId] = ($orderCounters[$courseId] ?? 0) + 1;

                return $orderCounters[$courseId];
            },
            'estimated_duration_minutes' => fake()->numberBetween(15, 120),
        ];
    }
}
