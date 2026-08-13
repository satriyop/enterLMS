<?php

namespace Database\Seeders;

use App\Domain\LearningPath\Services\PathEnrollmentService;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Tag;
use App\Models\User;
use App\Services\SeederThumbnailGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentAcademyCourseSeeder extends Seeder
{
    public const RESTRICTED_COURSE_TITLE = 'Administrasi Agen OpenClaw';

    public const OPERATOR_PATH_TITLE = 'Jalur Operator OpenClaw';

    public const OPERATOR_EMAIL = 'operator@enterlms.test';

    public function run(): void
    {
        $contentManager = User::query()->where('role', 'lms_admin')->first();
        $lmsAdmin = User::query()->where('role', 'lms_admin')->first();

        if (! $contentManager || ! $lmsAdmin) {
            $this->command?->warn('Content manager or LMS admin not found. Skipping agent academy seeding.');

            return;
        }

        $intro = Course::query()->where('title', FreeFlowDemoSeeder::FREE_COURSE_TITLE)->first();
        if (! $intro) {
            $this->command?->warn('Pengenalan Agen AI not found. Run FreeFlowDemoSeeder first.');

            return;
        }

        $openClaw = $this->seedOpenClawCourse($contentManager, $lmsAdmin);
        $path = $this->seedOperatorPath($contentManager, $intro, $openClaw);
        $this->seedOperatorEnrollment($lmsAdmin, $path);
    }

    private function seedOpenClawCourse(User $contentManager, User $admin): Course
    {
        $existing = Course::query()->where('title', self::RESTRICTED_COURSE_TITLE)->first();
        if ($existing) {
            $this->command?->info('Restricted course already exists: '.self::RESTRICTED_COURSE_TITLE);

            return $existing->load('sections.lessons');
        }

        $category = Category::query()->firstOrCreate(
            ['slug' => 'operasi-agen'],
            [
                'name' => 'Operasi Agen',
                'description' => 'Kursus terbatas untuk Operator yang menjalankan runtime agen.',
                'order' => 2,
            ]
        );

        $thumbnailPath = app(SeederThumbnailGenerator::class)->generate(
            self::RESTRICTED_COURSE_TITLE,
            'courses/thumbnails',
            'administrasi-agen-openclaw.jpg'
        );

        $course = Course::query()->create([
            'user_id' => $contentManager->id,
            'title' => self::RESTRICTED_COURSE_TITLE,
            'slug' => Str::slug(self::RESTRICTED_COURSE_TITLE).'-ops',
            'short_description' => 'Operasi harian OpenClaw untuk Operator Enteraksi: deploy, log, kill switch, konektor, isolasi tenant.',
            'long_description' => 'Course terbatas. Enrollment diberikan LMS Admin lewat Jalur Operator OpenClaw setelah Pengenalan Agen AI selesai. Bukan Lab dan bukan control plane.',
            'objectives' => [
                'Menjalankan operasi harian Deployment OpenClaw',
                'Memakai kill switch dan membaca log tanpa menyentuh keputusan bisnis tenant',
                'Menjaga isolasi tenant dan batas wewenang Operator',
            ],
            'prerequisites' => [
                'Wajib lulus Pengenalan Agen AI',
            ],
            'category_id' => $category->id,
            'thumbnail_path' => $thumbnailPath,
            'status' => 'published',
            'visibility' => 'restricted',
            'difficulty_level' => 'intermediate',
            'estimated_duration_minutes' => 90,
            'is_paid' => false,
            'price' => null,
            'published_at' => now(),
            'published_by' => $admin->id,
        ]);

        $tag = Tag::query()->firstOrCreate(
            ['slug' => 'openclaw'],
            ['name' => 'OpenClaw']
        );
        $course->tags()->syncWithoutDetaching([$tag->id]);

        $sections = [
            [
                'title' => 'Pekerjaan Operator',
                'description' => 'Apa yang Operator kerjakan, dan apa yang tidak',
                'lessons' => [
                    [
                        'title' => 'Lingkup operasi harian',
                        'duration' => 10,
                        'body' => 'Operator menjalankan Deployment: hidupkan, restart, amati kesehatan. Operator tidak memutuskan kebijakan tenant dan tidak menulis skill di academy ini.',
                    ],
                    [
                        'title' => 'Yang tidak boleh disentuh',
                        'duration' => 10,
                        'body' => 'Jangan mengubah keputusan bisnis tenant, jangan mencampur data antar-tenant, jangan memakai academy ini sebagai konsol runtime.',
                    ],
                ],
            ],
            [
                'title' => 'Runtime OpenClaw',
                'description' => 'Deploy, observasi, dan menghentikan lalu lintas',
                'lessons' => [
                    [
                        'title' => 'Deploy dan restart',
                        'duration' => 15,
                        'body' => 'Siklus hidup Deployment: provision, restart, dan memverifikasi layanan kembali sehat sebelum menyerahkan ke tenant.',
                    ],
                    [
                        'title' => 'Log dan kesehatan konektor',
                        'duration' => 15,
                        'body' => 'Baca log untuk membedakan kegagalan runtime, konektor, dan kesalahan konfigurasi tenant. Catat sebelum bertindak.',
                    ],
                    [
                        'title' => 'Kill switch',
                        'duration' => 15,
                        'body' => 'Kill switch memotong lalu lintas berbahaya per konektor atau per tenant. Pakai ketika merugikan, lalu laporkan. Ini wewenang Operator, bukan Tenant Admin.',
                    ],
                    [
                        'title' => 'Kredensial, konektor, isolasi tenant',
                        'duration' => 15,
                        'body' => 'Kredensial dan sesi konektor milik tenant. Isolasi VPS dan memori bukan opsi — kebocoran lintas tenant adalah kegagalan operasi.',
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
                    'content_type' => 'text',
                    'rich_content' => $this->richContent($lessonData['title'], $lessonData['body']),
                    'estimated_duration_minutes' => $lessonData['duration'],
                    'is_free_preview' => false,
                ]);
            }
        }

        $this->command?->info('Created restricted course: '.self::RESTRICTED_COURSE_TITLE);

        return $course->load('sections.lessons');
    }

    private function seedOperatorPath(User $contentManager, Course $intro, Course $openClaw): LearningPath
    {
        $existing = LearningPath::query()->where('title', self::OPERATOR_PATH_TITLE)->first();
        if ($existing) {
            $this->command?->info('Operator path already exists: '.self::OPERATOR_PATH_TITLE);

            return $existing;
        }

        $thumbnailPath = app(SeederThumbnailGenerator::class)->generate(
            self::OPERATOR_PATH_TITLE,
            'learning_paths/thumbnails',
            'jalur-operator-openclaw.jpg'
        );

        $path = LearningPath::query()->create([
            'title' => self::OPERATOR_PATH_TITLE,
            'slug' => Str::slug(self::OPERATOR_PATH_TITLE).'-ops',
            'description' => 'Kurikulum Operator: lulus Pengenalan Agen AI, lalu Administrasi Agen OpenClaw. Tidak terbuka untuk self-enroll publik.',
            'objectives' => [
                'Menyelesaikan pengenalan agen sebelum menyentuh operasi runtime',
                'Menjalankan operasi harian OpenClaw dengan batas wewenang yang jelas',
            ],
            'created_by' => $contentManager->id,
            'updated_by' => $contentManager->id,
            'is_published' => true,
            'visibility' => 'restricted',
            'published_at' => now(),
            'estimated_duration' => 135,
            'difficulty_level' => 'intermediate',
            'thumbnail_url' => $thumbnailPath,
            'prerequisite_mode' => 'sequential',
        ]);

        $path->courses()->attach($intro->id, [
            'position' => 1,
            'is_required' => true,
            'min_completion_percentage' => 80,
            'prerequisites' => null,
        ]);
        $path->courses()->attach($openClaw->id, [
            'position' => 2,
            'is_required' => true,
            'min_completion_percentage' => 80,
            'prerequisites' => [$intro->id],
        ]);

        $this->command?->info('Created restricted learning path: '.self::OPERATOR_PATH_TITLE);

        return $path;
    }

    private function seedOperatorEnrollment(User $admin, LearningPath $path): void
    {
        $operator = User::query()->updateOrCreate(
            ['email' => self::OPERATOR_EMAIL],
            [
                'name' => 'Raka Operator',
                'role' => 'learner',
                'password' => Hash::make(FreeFlowDemoSeeder::DEMO_PASSWORD),
                'email_verified_at' => now(),
            ]
        );

        $enrollmentService = app(PathEnrollmentService::class);
        if ($enrollmentService->isEnrolled($operator, $path)) {
            return;
        }

        $enrollmentService->enrollByAdmin($admin, $operator, $path);
        $this->command?->info('Enrolled '.self::OPERATOR_EMAIL.' in '.self::OPERATOR_PATH_TITLE);
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
}
