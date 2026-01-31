<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Alasan drop dalam konteks perbankan Indonesia.
     */
    private array $dropReasons = [
        'Promosi ke posisi lain yang tidak memerlukan pelatihan ini',
        'Mutasi ke cabang dengan prioritas pelatihan berbeda',
        'Cuti panjang karena alasan pribadi',
        'Beban kerja operasional terlalu tinggi',
        'Mengikuti pelatihan eksternal serupa yang disetujui manajemen',
        'Perubahan kebijakan pelatihan divisi',
        'Tidak memenuhi prasyarat teknis yang diperlukan',
        'Terlambat menyelesaikan modul awal sesuai deadline',
    ];

    public function run(): void
    {
        $learners = User::where('role', 'learner')->get();
        $publishedCourses = Course::where('status', 'published')->get();

        if ($learners->isEmpty()) {
            $this->command->warn('No learner users found. Run DatabaseSeeder first.');

            return;
        }

        if ($publishedCourses->isEmpty()) {
            $this->command->warn('No published courses found. Run CourseSeeder first.');

            return;
        }

        // Check if seeder has already run (10+ enrollments means it likely ran)
        $existingCount = Enrollment::count();
        if ($existingCount >= 10) {
            $this->command->info("EnrollmentSeeder appears to have already run ({$existingCount} enrollments exist). Skipping.");

            return;
        }

        $this->command->info('Creating enrollments for learners...');

        $trainerUser = User::where('role', 'trainer')->first();

        foreach ($learners as $learner) {
            // Skip courses where this learner already has an enrollment
            $existingCourseIds = Enrollment::where('user_id', $learner->id)->pluck('course_id');
            $availableCourses = $publishedCourses->reject(fn ($c) => $existingCourseIds->contains($c->id));

            if ($availableCourses->isEmpty()) {
                continue;
            }

            // Each learner gets 2-4 enrollments (unique courses only)
            $numEnrollments = rand(2, min(4, $availableCourses->count()));
            $selectedCourses = $availableCourses->shuffle()->take($numEnrollments);

            foreach ($selectedCourses as $course) {
                $enrollmentType = $this->randomEnrollmentType();

                match ($enrollmentType) {
                    'active_no_progress' => $this->createActiveEnrollment($learner, $course, 0, $trainerUser),
                    'active_25' => $this->createActiveEnrollment($learner, $course, 25, $trainerUser),
                    'active_50' => $this->createActiveEnrollment($learner, $course, 50, $trainerUser),
                    'active_75' => $this->createActiveEnrollment($learner, $course, 75, $trainerUser),
                    'completed' => $this->createCompletedEnrollment($learner, $course, $trainerUser),
                    'dropped' => $this->createDroppedEnrollment($learner, $course, $trainerUser),
                };
            }
        }

        $totalEnrollments = Enrollment::count();
        $this->command->info("Created {$totalEnrollments} enrollments with varying progress.");
    }

    /**
     * Random enrollment type dengan distribusi realistis.
     */
    private function randomEnrollmentType(): string
    {
        $rand = rand(1, 100);

        return match (true) {
            $rand <= 15 => 'active_no_progress',  // 15%
            $rand <= 35 => 'active_25',           // 20%
            $rand <= 55 => 'active_50',           // 20%
            $rand <= 70 => 'active_75',           // 15%
            $rand <= 85 => 'completed',           // 15%
            default => 'dropped',                 // 15%
        };
    }

    /**
     * Create enrollment with active status and specific progress percentage.
     */
    private function createActiveEnrollment(
        User $learner,
        Course $course,
        int $progressPercentage,
        ?User $inviter
    ): void {
        $enrolledDaysAgo = rand(1, 90);
        $enrollment = Enrollment::factory()
            ->active()
            ->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
                'progress_percentage' => $progressPercentage,
                'enrolled_at' => now()->subDays($enrolledDaysAgo),
                'started_at' => $progressPercentage > 0 ? now()->subDays($enrolledDaysAgo - rand(1, 5)) : null,
                'invited_by' => $inviter?->id,
            ]);

        if ($progressPercentage > 0) {
            $this->createLessonProgress($enrollment, $progressPercentage);
        }

        $this->command->info("  Created active enrollment ({$progressPercentage}% progress): {$learner->name} -> {$course->title}");
    }

    /**
     * Create completed enrollment.
     */
    private function createCompletedEnrollment(User $learner, Course $course, ?User $inviter): void
    {
        $enrolledDaysAgo = rand(30, 120);
        $completedDaysAgo = rand(1, $enrolledDaysAgo - 1);

        $enrollment = Enrollment::factory()
            ->completed()
            ->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
                'enrolled_at' => now()->subDays($enrolledDaysAgo),
                'started_at' => now()->subDays($enrolledDaysAgo - 2),
                'completed_at' => now()->subDays($completedDaysAgo),
                'invited_by' => $inviter?->id,
            ]);

        // Complete all lessons
        $this->createLessonProgress($enrollment, 100);

        $this->command->info("  Created completed enrollment: {$learner->name} -> {$course->title}");
    }

    /**
     * Create dropped enrollment.
     */
    private function createDroppedEnrollment(User $learner, Course $course, ?User $inviter): void
    {
        $enrolledDaysAgo = rand(15, 90);
        $progressBeforeDrop = rand(0, 60); // Dropped users typically have low progress

        $enrollment = Enrollment::factory()
            ->dropped()
            ->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
                'progress_percentage' => $progressBeforeDrop,
                'enrolled_at' => now()->subDays($enrolledDaysAgo),
                'started_at' => $progressBeforeDrop > 0 ? now()->subDays($enrolledDaysAgo - 2) : null,
                'invited_by' => $inviter?->id,
            ]);

        if ($progressBeforeDrop > 0) {
            $this->createLessonProgress($enrollment, $progressBeforeDrop);
        }

        $this->command->info("  Created dropped enrollment: {$learner->name} -> {$course->title}");
    }

    /**
     * Create lesson progress records for enrollment based on target percentage.
     */
    private function createLessonProgress(Enrollment $enrollment, int $targetPercentage): void
    {
        $lessons = Lesson::query()
            ->whereHas('section', fn ($q) => $q->where('course_id', $enrollment->course_id))
            ->orderBy('order')
            ->get();

        if ($lessons->isEmpty()) {
            return;
        }

        // Calculate how many lessons should be completed/in-progress
        $totalLessons = $lessons->count();
        $targetCompletedLessons = (int) ceil(($targetPercentage / 100) * $totalLessons);

        foreach ($lessons->take($targetCompletedLessons) as $index => $lesson) {
            $isLastLesson = ($index + 1) === $targetCompletedLessons;

            if ($isLastLesson && $targetPercentage < 100) {
                // Last lesson in progress (not completed)
                $this->createInProgressLessonProgress($enrollment, $lesson);
            } else {
                // Completed lesson
                $this->createCompletedLessonProgress($enrollment, $lesson);
            }
        }
    }

    /**
     * Create completed lesson progress.
     */
    private function createCompletedLessonProgress(Enrollment $enrollment, Lesson $lesson): void
    {
        $timeSpent = rand(60, $lesson->estimated_duration_minutes * 60 * 1.5);
        $completedAt = now()->subDays(rand(1, 30));

        $data = [
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
            'time_spent_seconds' => $timeSpent,
            'is_completed' => true,
            'last_viewed_at' => $completedAt,
            'completed_at' => $completedAt,
        ];

        // Add type-specific progress data
        if (in_array($lesson->content_type, ['video', 'youtube', 'audio'])) {
            $duration = $lesson->estimated_duration_minutes * 60;
            $data = array_merge($data, [
                'media_position_seconds' => $duration,
                'media_duration_seconds' => $duration,
                'media_progress_percentage' => 100,
            ]);
        } else {
            // Text/document content with pagination
            $totalPages = rand(5, 20);
            $data = array_merge($data, [
                'current_page' => $totalPages,
                'total_pages' => $totalPages,
                'highest_page_reached' => $totalPages,
            ]);
        }

        LessonProgress::factory()->create($data);
    }

    /**
     * Create in-progress lesson progress.
     */
    private function createInProgressLessonProgress(Enrollment $enrollment, Lesson $lesson): void
    {
        $timeSpent = rand(30, $lesson->estimated_duration_minutes * 60 * 0.7);
        $lastViewed = now()->subDays(rand(0, 7));

        $data = [
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
            'time_spent_seconds' => $timeSpent,
            'is_completed' => false,
            'last_viewed_at' => $lastViewed,
            'completed_at' => null,
        ];

        // Add type-specific progress data
        if (in_array($lesson->content_type, ['video', 'youtube', 'audio'])) {
            $duration = $lesson->estimated_duration_minutes * 60;
            $position = rand((int) ($duration * 0.2), (int) ($duration * 0.8));
            $percentage = min(99, ($position / $duration) * 100);

            $data = array_merge($data, [
                'media_position_seconds' => $position,
                'media_duration_seconds' => $duration,
                'media_progress_percentage' => round($percentage, 2),
            ]);
        } else {
            // Text/document content with pagination
            $totalPages = rand(5, 20);
            $currentPage = rand(1, $totalPages - 1);
            $data = array_merge($data, [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'highest_page_reached' => $currentPage,
            ]);
        }

        LessonProgress::factory()->create($data);
    }
}
