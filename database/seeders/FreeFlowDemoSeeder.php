<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds a realistic free-flow demo for local/manual testing.
 *
 * Covers the core LMS path without payment:
 * users → free published course with content → optional assessment.
 *
 * Run alone:
 *   php artisan db:seed --class=FreeFlowDemoSeeder
 *
 * Default password for all demo accounts: password
 */
class FreeFlowDemoSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    public const FREE_COURSE_TITLE = 'Orientasi Kepatuhan Perbankan (Demo Gratis)';

    /**
     * @var array<int, array{name: string, email: string, role: string}>
     */
    private array $demoUsers = [
        [
            'name' => 'Budi Santoso',
            'email' => 'learner@enteraksi.test',
            'role' => 'learner',
        ],
        [
            'name' => 'Siti Rahayu',
            'email' => 'content@enteraksi.test',
            'role' => 'content_manager',
        ],
        [
            'name' => 'Andi Wijaya',
            'email' => 'trainer@enteraksi.test',
            'role' => 'trainer',
        ],
        [
            'name' => 'Dewi Lestari',
            'email' => 'admin@enteraksi.test',
            'role' => 'lms_admin',
        ],
    ];

    public function run(): void
    {
        $users = $this->seedUsers();
        $category = $this->ensureCategory();
        $course = $this->seedFreeDemoCourse($users['content_manager'], $users['lms_admin'], $category);
        $this->seedOptionalQuiz($course, $users['content_manager']);

        $this->printSummary($users, $course);
    }

    /**
     * @return array{learner: User, content_manager: User, trainer: User, lms_admin: User}
     */
    private function seedUsers(): array
    {
        $map = [];

        foreach ($this->demoUsers as $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'role' => $userData['role'],
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'email_verified_at' => now(),
                ]
            );

            $map[$userData['role']] = $user;
        }

        return [
            'learner' => $map['learner'],
            'content_manager' => $map['content_manager'],
            'trainer' => $map['trainer'],
            'lms_admin' => $map['lms_admin'],
        ];
    }

    private function ensureCategory(): Category
    {
        return Category::query()->firstOrCreate(
            ['slug' => 'kepatuhan-perbankan'],
            [
                'name' => 'Kepatuhan Perbankan',
                'description' => 'Pelatihan kepatuhan, regulasi OJK, dan tata kelola untuk industri jasa keuangan.',
            ]
        );
    }

    private function seedFreeDemoCourse(User $contentManager, User $admin, Category $category): Course
    {
        $course = Course::query()->where('title', self::FREE_COURSE_TITLE)->first();

        if ($course) {
            $this->command?->info('Free demo course already exists: '.self::FREE_COURSE_TITLE);

            return $course->load('sections.lessons');
        }

        $course = Course::query()->create([
            'user_id' => $contentManager->id,
            'title' => self::FREE_COURSE_TITLE,
            'slug' => Str::slug(self::FREE_COURSE_TITLE).'-demo',
            'short_description' => 'Kursus onboarding gratis untuk memahami alur belajar Enteraksi: daftar, belajar, selesai, dan dapat sertifikat.',
            'long_description' => 'Kursus demo gratis ini dirancang agar peserta baru bisa merasakan alur lengkap LMS Enteraksi tanpa pembayaran. Materi mencakup pengenalan kepatuhan perbankan, peran OJK, dan praktik pelaporan dasar.',
            'objectives' => [
                'Memahami alur belajar di Enteraksi LMS',
                'Mengenal konsep dasar kepatuhan perbankan',
                'Menyelesaikan kursus gratis hingga memperoleh sertifikat',
            ],
            'prerequisites' => [
                'Tidak ada prasyarat — cocok untuk peserta baru',
            ],
            'category_id' => $category->id,
            'status' => 'published',
            'visibility' => 'public',
            'difficulty_level' => 'beginner',
            'estimated_duration_minutes' => 45,
            'is_paid' => false,
            'price' => null,
            'published_at' => now(),
            'published_by' => $admin->id,
        ]);

        $complianceTag = Tag::query()->firstOrCreate(
            ['slug' => 'kepatuhan'],
            ['name' => 'Kepatuhan']
        );
        $course->tags()->syncWithoutDetaching([$complianceTag->id]);

        $sections = [
            [
                'title' => 'Memulai di Enteraksi',
                'description' => 'Orientasi platform dan cara menyelesaikan kursus',
                'lessons' => [
                    [
                        'title' => 'Selamat Datang di Enteraksi LMS',
                        'content_type' => 'text',
                        'duration' => 5,
                        'is_free_preview' => true,
                        'rich_content' => $this->richContent(
                            'Selamat Datang di Enteraksi LMS',
                            'Enteraksi adalah platform pembelajaran untuk industri perbankan. Selesaikan setiap pelajaran untuk menandai progres Anda, lalu selesaikan kursus untuk memperoleh sertifikat.'
                        ),
                    ],
                    [
                        'title' => 'Cara Menyelesaikan Pelajaran',
                        'content_type' => 'text',
                        'duration' => 5,
                        'rich_content' => $this->richContent(
                            'Cara Menyelesaikan Pelajaran',
                            'Buka setiap pelajaran secara berurutan. Progres tersimpan otomatis. Setelah semua pelajaran selesai, status enrollment Anda menjadi completed dan sertifikat diterbitkan otomatis.'
                        ),
                    ],
                ],
            ],
            [
                'title' => 'Dasar Kepatuhan Perbankan',
                'description' => 'Materi singkat regulasi dan budaya kepatuhan',
                'lessons' => [
                    [
                        'title' => 'Apa itu Kepatuhan (Compliance)?',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->richContent(
                            'Apa itu Kepatuhan (Compliance)?',
                            'Kepatuhan adalah kepatuhan bank terhadap peraturan perundang-undangan, ketentuan OJK, serta kebijakan internal. Tujuannya melindungi nasabah, bank, dan sistem keuangan.'
                        ),
                    ],
                    [
                        'title' => 'Peran OJK dalam Industri Perbankan',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->richContent(
                            'Peran OJK dalam Industri Perbankan',
                            'OJK mengatur dan mengawasi lembaga jasa keuangan, termasuk bank. Pegawai bank wajib memahami POJK relevan agar operasional tetap aman dan patuh.'
                        ),
                    ],
                    [
                        'title' => 'Ringkasan & Langkah Berikutnya',
                        'content_type' => 'text',
                        'duration' => 5,
                        'rich_content' => $this->richContent(
                            'Ringkasan & Langkah Berikutnya',
                            'Anda telah menyelesaikan materi demo. Cek halaman Sertifikat Saya untuk mengunduh PDF dan memverifikasi kode sertifikat secara publik.'
                        ),
                    ],
                ],
            ],
        ];

        foreach ($sections as $sectionOrder => $sectionData) {
            $section = CourseSection::query()->create([
                'course_id' => $course->id,
                'title' => $sectionData['title'],
                'description' => $sectionData['description'],
                'order' => $sectionOrder + 1,
            ]);

            foreach ($sectionData['lessons'] as $lessonOrder => $lessonData) {
                Lesson::query()->create([
                    'course_section_id' => $section->id,
                    'title' => $lessonData['title'],
                    'description' => null,
                    'order' => $lessonOrder + 1,
                    'content_type' => $lessonData['content_type'],
                    'rich_content' => $lessonData['rich_content'],
                    'estimated_duration_minutes' => $lessonData['duration'],
                    'is_free_preview' => $lessonData['is_free_preview'] ?? false,
                ]);
            }
        }

        return $course->load('sections.lessons');
    }

    private function seedOptionalQuiz(Course $course, User $contentManager): void
    {
        if ($course->assessments()->exists()) {
            return;
        }

        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'user_id' => $contentManager->id,
            'title' => 'Kuis Singkat Orientasi Kepatuhan',
            'slug' => 'kuis-orientasi-kepatuhan-demo-'.Str::lower(Str::random(6)),
            'description' => 'Kuis opsional untuk menguji pemahaman materi demo.',
            'status' => 'published',
            'passing_score' => 60,
            'max_attempts' => 3,
            'is_required' => false,
            'time_limit_minutes' => null,
            'published_at' => now(),
        ]);

        $question = Question::query()->create([
            'assessment_id' => $assessment->id,
            'question_text' => 'OJK adalah lembaga yang mengatur dan mengawasi industri jasa keuangan di Indonesia.',
            'question_type' => 'true_false',
            'points' => 10,
            'order' => 1,
            'correct_answer' => 'true',
        ]);

        QuestionOption::query()->create([
            'question_id' => $question->id,
            'option_text' => 'Benar',
            'is_correct' => true,
            'order' => 1,
        ]);

        QuestionOption::query()->create([
            'question_id' => $question->id,
            'option_text' => 'Salah',
            'is_correct' => false,
            'order' => 2,
        ]);
    }

    /**
     * @return array{type: string, content: array<int, array<string, mixed>>}
     */
    private function richContent(string $heading, string $paragraph): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 1],
                    'content' => [['type' => 'text', 'text' => $heading]],
                ],
                [
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => $paragraph]],
                ],
            ],
        ];
    }

    /**
     * @param  array{learner: User, content_manager: User, trainer: User, lms_admin: User}  $users
     */
    private function printSummary(array $users, Course $course): void
    {
        if (! $this->command) {
            return;
        }

        $lessonCount = $course->lessons()->count();

        $this->command->newLine();
        $this->command->info('=== Free Flow Demo Ready ===');
        $this->command->info("Course: {$course->title} (id={$course->id}, lessons={$lessonCount}, free)");
        $this->command->info('Password for all demo users: '.self::DEMO_PASSWORD);
        $this->command->table(
            ['Role', 'Name', 'Email'],
            [
                ['learner', $users['learner']->name, $users['learner']->email],
                ['content_manager', $users['content_manager']->name, $users['content_manager']->email],
                ['trainer', $users['trainer']->name, $users['trainer']->email],
                ['lms_admin', $users['lms_admin']->name, $users['lms_admin']->email],
            ]
        );
        $this->command->info('Suggested manual flow:');
        $this->command->line('  1. Login as learner@enteraksi.test');
        $this->command->line('  2. Buka kursus demo → Enroll');
        $this->command->line('  3. Selesaikan semua pelajaran');
        $this->command->line('  4. Buka /certificates untuk unduh sertifikat');
        $this->command->newLine();
    }
}
