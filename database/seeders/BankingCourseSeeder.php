<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Banking catalog is frozen. Use AgentAcademyCourseSeeder.
 */
class BankingCourseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('BankingCourseSeeder is frozen. Delegating to AgentAcademyCourseSeeder.');
        $this->call(AgentAcademyCourseSeeder::class);
    }
}
