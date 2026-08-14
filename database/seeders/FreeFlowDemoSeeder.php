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
use App\Services\SeederThumbnailGenerator;
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

    public const FREE_COURSE_TITLE = 'Pengenalan Agen AI';

    /**
     * @var array<int, array{name: string, email: string, role: string}>
     */
    private array $demoUsers = [
        [
            'name' => 'Budi Santoso',
            'email' => 'learner@enterlms.test',
            'role' => 'learner',
        ],
        [
            'name' => 'Dewi Lestari',
            'email' => 'admin@enterlms.test',
            'role' => 'lms_admin',
        ],
    ];

    public function run(): void
    {
        $users = $this->seedUsers();
        $category = $this->ensureCategory();
        $course = $this->seedFreeDemoCourse($users['lms_admin'], $users['lms_admin'], $category);
        $this->seedOptionalQuiz($course, $users['lms_admin']);

        $this->printSummary($users, $course);
    }

    /**
     * @return array{learner: User, lms_admin: User}
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
            'lms_admin' => $map['lms_admin'],
        ];
    }

    private function ensureCategory(): Category
    {
        return Category::query()->firstOrCreate(
            ['slug' => 'pengenalan-agen'],
            [
                'name' => 'Pengenalan Agen',
                'description' => 'Kursus terbuka tentang apa itu agen AI dan bagaimana academy ini bekerja.',
            ]
        );
    }

    private function seedFreeDemoCourse(User $author, User $admin, Category $category): Course
    {
        $course = Course::query()->where('title', self::FREE_COURSE_TITLE)->first();

        if ($course) {
            $this->command?->info('Free demo course already exists: '.self::FREE_COURSE_TITLE);

            return $course->load('sections.lessons');
        }

        $thumbnailPath = app(SeederThumbnailGenerator::class)->generate(
            self::FREE_COURSE_TITLE,
            'courses/thumbnails',
            'free-flow-demo-orientation.jpg'
        );

        $course = Course::query()->create([
            'user_id' => $author->id,
            'title' => self::FREE_COURSE_TITLE,
            'slug' => Str::slug(self::FREE_COURSE_TITLE).'-demo',
            'short_description' => 'Kursus gratis untuk memahami apa itu agen AI, apa yang dilakukan Enteraksi, dan apa yang tidak kamu operasikan di academy ini.',
            'long_description' => 'Pengenalan terbuka untuk siapa pun. Tidak ada asumsi kamu punya tenant. Kamu akan mengenal agen AI, peran Tenant Admin versus Operator, dan batas academy ini — EnterLMS bukan control plane.',
            'objectives' => [
                'Memahami apa itu agen AI dalam bahasa sehari-hari',
                'Membedakan peran Tenant Admin, Tenant Owner, dan Operator',
                'Mengetahui apa yang tidak dioperasikan di EnterLMS',
            ],
            'prerequisites' => [
                'Tidak ada prasyarat — terbuka untuk peserta baru',
            ],
            'category_id' => $category->id,
            'thumbnail_path' => $thumbnailPath,
            'status' => 'published',
            'visibility' => 'public',
            'difficulty_level' => 'beginner',
            'estimated_duration_minutes' => 45,
            'is_paid' => false,
            'price' => null,
            'published_at' => now(),
            'published_by' => $admin->id,
        ]);

        $agentTag = Tag::query()->firstOrCreate(
            ['slug' => 'agen-ai'],
            ['name' => 'Agen AI']
        );
        $course->tags()->syncWithoutDetaching([$agentTag->id]);

        $sections = [
            [
                'title' => 'Memulai di EnterLMS',
                'description' => 'Orientasi academy dan cara menyelesaikan kursus',
                'lessons' => [
                    [
                        'title' => 'Selamat Datang di EnterLMS',
                        'content_type' => 'text',
                        'duration' => 5,
                        'is_free_preview' => true,
                        'rich_content' => $this->richContent(
                            'Selamat Datang di EnterLMS',
                            'EnterLMS adalah academy untuk orang yang menjalankan dan membangun keluarga produk AI. Ini bukan sekolah AI generik dan bukan tempat men-deploy agen. Selesaikan setiap pelajaran untuk menandai progres, lalu selesaikan kursus.'
                        ),
                    ],
                    [
                        'title' => 'Cara Menyelesaikan Pelajaran',
                        'content_type' => 'text',
                        'duration' => 5,
                        'rich_content' => $this->richContent(
                            'Cara Menyelesaikan Pelajaran',
                            'Buka setiap pelajaran secara berurutan. Progres tersimpan otomatis. Setelah semua pelajaran selesai, enrollment menjadi completed dan sertifikat diterbitkan otomatis.'
                        ),
                    ],
                ],
            ],
            [
                'title' => 'Apa itu Agen AI',
                'description' => 'Pengenalan tanpa asumsi kamu punya tenant',
                'lessons' => [
                    [
                        'title' => 'Agen yang bekerja untuk manusia',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->richContent(
                            'Agen yang bekerja untuk manusia',
                            'Agen AI adalah program yang menerima tujuan, memakai alat, dan bertindak berulang. Di keluarga produk ini, agen itu dijalankan sebagai layanan terkelola — bukan chatbot yang kamu install sendiri.'
                        ),
                    ],
                    [
                        'title' => 'Enteraksi, Operator, dan Tenant',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->richContent(
                            'Enteraksi, Operator, dan Tenant',
                            'Enteraksi adalah control plane: Tenant Owner dan Tenant Admin mengatur anggota, knowledge, dan kebijakan tenant mereka. Operator Enteraksi menjalankan Deployment. EnterLMS hanya mengajarkan itu, tidak menjalankan runtime.'
                        ),
                    ],
                    [
                        'title' => 'Apa yang tidak kamu operasikan di sini',
                        'content_type' => 'text',
                        'duration' => 5,
                        'rich_content' => $this->richContent(
                            'Apa yang tidak kamu operasikan di sini',
                            'Tidak ada konsol agen hidup di academy ini. Menyelesaikan kursus ini tidak membuka akses produksi. Jika kamu Operator, LMS Admin akan memasukkanmu ke jalur Administrasi OpenClaw setelah kursus ini selesai.'
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

    private function seedOptionalQuiz(Course $course, User $author): void
    {
        if ($course->assessments()->exists()) {
            return;
        }

        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'user_id' => $author->id,
            'title' => 'Kuis Singkat Pengenalan Agen AI',
            'slug' => 'kuis-pengenalan-agen-ai-'.Str::lower(Str::random(6)),
            'description' => 'Kuis opsional untuk menguji pemahaman materi pengenalan.',
            'status' => 'published',
            'passing_score' => 60,
            'max_attempts' => 3,
            'is_required' => false,
            'time_limit_minutes' => null,
            'published_at' => now(),
        ]);

        $question = Question::query()->create([
            'assessment_id' => $assessment->id,
            'question_text' => 'EnterLMS adalah control plane untuk men-deploy agen OpenClaw.',
            'question_type' => 'true_false',
            'points' => 10,
            'order' => 1,
            'correct_answer' => 'false',
        ]);

        QuestionOption::query()->create([
            'question_id' => $question->id,
            'option_text' => 'Benar',
            'is_correct' => false,
            'order' => 1,
        ]);

        QuestionOption::query()->create([
            'question_id' => $question->id,
            'option_text' => 'Salah',
            'is_correct' => true,
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
     * @param  array{learner: User, lms_admin: User}  $users
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
                ['lms_admin', $users['lms_admin']->name, $users['lms_admin']->email],
            ]
        );
        $this->command->info('Suggested manual flow:');
        $this->command->line('  1. Login as learner@enterlms.test');
        $this->command->line('  2. Buka kursus demo → Enroll');
        $this->command->line('  3. Selesaikan semua pelajaran');
        $this->command->line('  4. Buka /certificates untuk unduh sertifikat');
        $this->command->newLine();
    }
}
