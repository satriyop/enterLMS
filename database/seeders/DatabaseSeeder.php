<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Default local/demo seed for EnterLMS (academy for the AI product family).
 *
 * Primary accounts come from FreeFlowDemoSeeder (password: password).
 * Content stack: open intro course → restricted OpenClaw course + operator path → sample activity.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Extra learners for enrollment volume demos (beyond free-flow accounts)
        $extraLearners = [
            ['name' => 'Ayu Lestari', 'email' => 'ayu.lestari@example.com'],
            ['name' => 'Rizky Pratama', 'email' => 'rizky.pratama@example.com'],
            ['name' => 'Maya Putri', 'email' => 'maya.putri@example.com'],
        ];

        foreach ($extraLearners as $learner) {
            User::query()->firstOrCreate(
                ['email' => $learner['email']],
                [
                    'name' => $learner['name'],
                    'role' => 'learner',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->call([
            // Demo users + open Pengenalan Agen AI
            FreeFlowDemoSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            // Restricted OpenClaw course + operator path
            AgentAcademyCourseSeeder::class,
            // Sample activity
            EnrollmentSeeder::class,
            AssessmentSeeder::class,
        ]);
    }
}
