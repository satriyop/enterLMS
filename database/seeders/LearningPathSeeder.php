<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Operator path lives in AgentAcademyCourseSeeder.
 */
class LearningPathSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AgentAcademyCourseSeeder::class);
    }
}
