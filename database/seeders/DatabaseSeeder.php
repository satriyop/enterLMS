<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Core demo accounts (password: password) — FreeFlowDemoSeeder will upsert richer profiles
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'learner',
            'password' => Hash::make('password'),
        ]);

        // Additional learners for enrollment variety
        User::factory()->count(4)->create([
            'role' => 'learner',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'Content Manager',
            'email' => 'content@example.com',
            'role' => 'content_manager',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'Trainer',
            'email' => 'trainer@example.com',
            'role' => 'trainer',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'LMS Admin',
            'email' => 'admin@example.com',
            'role' => 'lms_admin',
            'password' => Hash::make('password'),
        ]);

        $this->call([
            CategorySeeder::class,
            TagSeeder::class,
            CourseSeeder::class,
            BankingCourseSeeder::class,
            FreeFlowDemoSeeder::class,
            LearningPathSeeder::class,
            EnrollmentSeeder::class,
            AssessmentSeeder::class,
            QuestionBankSeeder::class,
        ]);
    }
}
