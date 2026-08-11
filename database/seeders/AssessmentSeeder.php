<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssessmentSeeder extends Seeder
{
    /**
     * Template judul assessment dalam konteks perbankan Indonesia.
     */
    private array $assessmentTitles = [
        'Kuis Pemahaman: %s',
        'Ujian Akhir: %s',
        'Evaluasi Kompetensi: %s',
        'Tes Sertifikasi: %s',
        'Quiz Pengetahuan: %s',
    ];

    /**
     * Template pertanyaan umum untuk banking/compliance context.
     */
    private array $questionTemplates = [
        'compliance' => [
            'Apa yang dimaksud dengan prinsip kehati-hatian (prudential principle) dalam perbankan?',
            'Sebutkan fungsi utama dari Otoritas Jasa Keuangan (OJK) dalam mengawasi perbankan.',
            'Apa saja komponen utama dalam penerapan Good Corporate Governance (GCG)?',
            'Jelaskan pentingnya sistem kontrol internal dalam operasional bank.',
        ],
        'apu' => [
            'Apa definisi pencucian uang menurut Undang-Undang yang berlaku di Indonesia?',
            'Sebutkan tahapan dalam proses Customer Due Diligence (CDD).',
            'Apa yang dimaksud dengan Beneficial Owner dalam konteks APU-PPT?',
            'Kapan bank wajib melaporkan transaksi keuangan mencurigakan (LTKM)?',
        ],
        'digital' => [
            'Apa yang dimaksud dengan Open Banking?',
            'Sebutkan risiko keamanan utama dalam digital banking.',
            'Jelaskan konsep API Economy dalam konteks perbankan.',
            'Apa saja prinsip keamanan siber yang harus diterapkan bank?',
        ],
        'risk' => [
            'Apa yang dimaksud dengan Basel III dalam manajemen risiko perbankan?',
            'Sebutkan jenis-jenis risiko utama yang dihadapi bank.',
            'Jelaskan perbedaan antara risiko kredit dan risiko operasional.',
            'Apa yang dimaksud dengan Capital Adequacy Ratio (CAR)?',
        ],
        'general' => [
            'Sebutkan produk simpanan yang umumnya ditawarkan oleh bank.',
            'Apa perbedaan antara bank umum dan BPR?',
            'Jelaskan fungsi Bank Indonesia sebagai bank sentral.',
            'Apa yang dimaksud dengan Lembaga Penjamin Simpanan (LPS)?',
        ],
    ];

    public function run(): void
    {
        $publishedCourses = Course::where('status', 'published')->get();
        $contentManager = User::where('role', 'content_manager')->first();
        $lmsAdmin = User::where('role', 'lms_admin')->first();

        if ($publishedCourses->isEmpty()) {
            $this->command->warn('No published courses found. Run BankingCourseSeeder / FreeFlowDemoSeeder first.');

            return;
        }

        if (! $contentManager || ! $lmsAdmin) {
            $this->command->warn('Content manager or LMS admin not found. Run DatabaseSeeder first.');

            return;
        }

        $this->command->info('Creating assessments for courses without assessments...');

        foreach ($publishedCourses as $course) {
            // Keep FreeFlow / manual assessments; only seed empty courses
            if ($course->assessments()->exists()) {
                continue;
            }

            // Create 1-2 assessments per course
            $numAssessments = rand(1, 2);

            for ($i = 0; $i < $numAssessments; $i++) {
                $isPublished = rand(1, 100) <= 70; // 70% chance of being published

                $assessment = $this->createAssessment($course, $contentManager, $lmsAdmin, $isPublished, $i + 1);
                $this->createQuestions($assessment);

                $this->command->info("  Created assessment ({$assessment->status}): {$assessment->title}");
            }
        }

        // Create assessment attempts for enrollments
        $this->createAssessmentAttempts();

        $totalAssessments = Assessment::count();
        $totalQuestions = Question::count();
        $totalAttempts = AssessmentAttempt::count();

        $this->command->info("\nCreated {$totalAssessments} assessments with {$totalQuestions} questions and {$totalAttempts} attempts.");
    }

    /**
     * Create an assessment for a course.
     */
    private function createAssessment(
        Course $course,
        User $creator,
        User $publisher,
        bool $isPublished,
        int $sequence
    ): Assessment {
        $titleTemplate = $this->assessmentTitles[array_rand($this->assessmentTitles)];
        $title = sprintf($titleTemplate, $course->title);

        // Add sequence number if multiple assessments
        if ($sequence > 1) {
            $title .= " (Bagian {$sequence})";
        }

        $factory = Assessment::factory();

        if ($isPublished) {
            $factory = $factory->published();
        }

        return $factory->create([
            'course_id' => $course->id,
            'user_id' => $creator->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => "Assessment untuk mengukur pemahaman Anda terhadap materi {$course->title}.",
            'instructions' => $this->generateInstructions(),
            'time_limit_minutes' => rand(0, 1) ? rand(30, 90) : null,
            'passing_score' => rand(60, 80),
            'max_attempts' => rand(1, 3),
            'shuffle_questions' => rand(0, 1),
            'show_correct_answers' => rand(0, 1),
            'allow_review' => true,
            'is_required' => rand(0, 1),
            'published_at' => $isPublished ? now()->subDays(rand(1, 30)) : null,
            'published_by' => $isPublished ? $publisher->id : null,
        ]);
    }

    /**
     * Generate realistic assessment instructions in Bahasa Indonesia.
     */
    private function generateInstructions(): string
    {
        return <<<'INSTRUCTIONS'
**Petunjuk Pengerjaan:**

1. Baca setiap pertanyaan dengan cermat sebelum menjawab
2. Pilih jawaban yang paling tepat untuk setiap pertanyaan
3. Pastikan Anda telah menjawab semua pertanyaan sebelum submit
4. Setelah waktu habis, jawaban akan otomatis tersimpan
5. Anda dapat meninjau kembali jawaban sebelum submit final

**Penting:** Pastikan koneksi internet Anda stabil selama mengerjakan assessment ini.
INSTRUCTIONS;
    }

    /**
     * Create 3-5 questions with options for an assessment.
     */
    private function createQuestions(Assessment $assessment): void
    {
        $numQuestions = rand(3, 5);
        $questionCategory = $this->determineQuestionCategory($assessment);

        for ($order = 1; $order <= $numQuestions; $order++) {
            $questionType = $this->randomQuestionType();

            $question = Question::factory()
                ->create([
                    'assessment_id' => $assessment->id,
                    'question_text' => $this->getQuestionText($questionCategory, $order),
                    'question_type' => $questionType,
                    'points' => rand(1, 5),
                    'order' => $order,
                ]);

            // Create options for multiple choice and true/false questions
            if (in_array($questionType, ['multiple_choice', 'true_false'])) {
                $this->createQuestionOptions($question, $questionType);
            }
        }
    }

    /**
     * Determine question category based on course title.
     */
    private function determineQuestionCategory(Assessment $assessment): string
    {
        $title = strtolower($assessment->course->title);

        return match (true) {
            str_contains($title, 'regulasi') || str_contains($title, 'gcg') || str_contains($title, 'kontrol') => 'compliance',
            str_contains($title, 'pencucian') || str_contains($title, 'cdd') => 'apu',
            str_contains($title, 'digital') || str_contains($title, 'siber') || str_contains($title, 'api') => 'digital',
            str_contains($title, 'risiko') || str_contains($title, 'basel') => 'risk',
            default => 'general',
        };
    }

    /**
     * Get question text from templates.
     */
    private function getQuestionText(string $category, int $order): string
    {
        $templates = $this->questionTemplates[$category] ?? $this->questionTemplates['general'];

        return $templates[($order - 1) % count($templates)];
    }

    /**
     * Random question type with realistic distribution.
     */
    private function randomQuestionType(): string
    {
        $rand = rand(1, 100);

        return match (true) {
            $rand <= 70 => 'multiple_choice',  // 70%
            $rand <= 85 => 'true_false',       // 15%
            $rand <= 95 => 'short_answer',     // 10%
            default => 'essay',                // 5%
        };
    }

    /**
     * Create options for a question.
     */
    private function createQuestionOptions(Question $question, string $questionType): void
    {
        if ($questionType === 'true_false') {
            // True/False options
            QuestionOption::factory()->correct()->create([
                'question_id' => $question->id,
                'option_text' => 'Benar',
                'order' => 1,
            ]);

            QuestionOption::factory()->incorrect()->create([
                'question_id' => $question->id,
                'option_text' => 'Salah',
                'order' => 2,
            ]);
        } else {
            // Multiple choice: 4 options, 1 correct
            $options = [
                'Pilihan jawaban yang benar',
                'Pilihan jawaban yang kurang tepat',
                'Pilihan jawaban yang tidak tepat',
                'Pilihan jawaban yang salah',
            ];

            shuffle($options);

            foreach ($options as $index => $optionText) {
                QuestionOption::factory()
                    ->state(['is_correct' => $index === 0])
                    ->create([
                        'question_id' => $question->id,
                        'option_text' => $optionText,
                        'order' => $index + 1,
                    ]);
            }
        }
    }

    /**
     * Create assessment attempts for existing enrollments.
     */
    private function createAssessmentAttempts(): void
    {
        // Get enrollments with active or completed status
        $enrollments = Enrollment::query()
            ->whereIn('status', ['active', 'completed'])
            ->with(['user', 'course.assessments'])
            ->get();

        if ($enrollments->isEmpty()) {
            $this->command->warn('  No active/completed enrollments found. Skipping attempt creation.');

            return;
        }

        $lmsAdmin = User::where('role', 'lms_admin')->first();
        $attemptCount = 0;

        foreach ($enrollments as $enrollment) {
            $publishedAssessments = $enrollment->course->assessments()
                ->where('status', 'published')
                ->get();

            if ($publishedAssessments->isEmpty()) {
                continue;
            }

            // 50% chance user has attempted at least one assessment
            if (rand(0, 1) === 0) {
                continue;
            }

            // Attempt 1-2 assessments
            $assessmentsToAttempt = $publishedAssessments->random(min(rand(1, 2), $publishedAssessments->count()));

            foreach ($assessmentsToAttempt as $assessment) {
                $numAttempts = rand(1, min(2, $assessment->max_attempts));

                for ($attemptNum = 1; $attemptNum <= $numAttempts; $attemptNum++) {
                    $this->createAttempt($assessment, $enrollment->user, $attemptNum, $lmsAdmin);
                    $attemptCount++;
                }
            }
        }

        $this->command->info("  Created {$attemptCount} assessment attempts");
    }

    /**
     * Create a single assessment attempt.
     */
    private function createAttempt(
        Assessment $assessment,
        User $user,
        int $attemptNumber,
        ?User $grader
    ): void {
        $status = $this->randomAttemptStatus();

        $factory = AssessmentAttempt::factory();

        match ($status) {
            'in_progress' => $factory->inProgress(),
            'submitted' => $factory->submitted(),
            'graded' => $factory->graded(),
            default => $factory->graded(),
        };

        $factory->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'attempt_number' => $attemptNumber,
            'started_at' => now()->subDays(rand(1, 30)),
            'graded_by' => $status === 'graded' ? $grader?->id : null,
        ]);
    }

    /**
     * Random attempt status with realistic distribution.
     */
    private function randomAttemptStatus(): string
    {
        $rand = rand(1, 100);

        return match (true) {
            $rand <= 70 => 'graded',       // 70%
            $rand <= 85 => 'submitted',    // 15%
            default => 'in_progress',      // 15%
        };
    }
}
