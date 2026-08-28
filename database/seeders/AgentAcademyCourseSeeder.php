<?php

namespace Database\Seeders;

use App\Domain\LearningPath\Services\PathEnrollmentService;
use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\LearningPath;
use App\Models\Tag;
use App\Models\User;
use App\Services\SeederThumbnailGenerator;
use Database\Seeders\Concerns\BuildsAcademyLessonContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentAcademyCourseSeeder extends Seeder
{
    use BuildsAcademyLessonContent;

    public const RESTRICTED_COURSE_TITLE = 'Administrasi Agen OpenClaw';

    public const OPERATOR_PATH_TITLE = 'Jalur Operator OpenClaw';

    public const OPERATOR_EMAIL = 'operator@enterlms.test';

    public const FINAL_EXAM_TITLE = 'Ujian Akhir Administrasi Agen OpenClaw';

    public const INCIDENT_REVIEW_URL = 'https://zoom.us/j/5550001111';

    /**
     * @var list<string>
     */
    public const LESSON_TITLES = [
        'Lingkup operasi harian',
        'Briefing shift Operator',
        'Yang tidak boleh disentuh',
        'Deploy dan restart',
        'Walkthrough observasi',
        'Runbook kesehatan konektor',
        'Kill switch',
        'Kredensial, konektor, isolasi tenant',
        'Review insiden (tabletop)',
        'Ringkasan wewenang Operator',
    ];

    public function run(): void
    {
        $lmsAdmin = User::query()->where('role', 'lms_admin')->first();

        if (! $lmsAdmin) {
            $this->command?->warn('LMS Admin not found. Skipping agent academy seeding.');

            return;
        }

        $intro = Course::query()->where('title', FreeFlowDemoSeeder::FREE_COURSE_TITLE)->first();
        if (! $intro) {
            $this->command?->warn('Pengenalan Agen AI not found. Run FreeFlowDemoSeeder first.');

            return;
        }

        $openClaw = $this->seedOpenClawCourse($lmsAdmin, $lmsAdmin);
        $this->seedFinalExam($openClaw, $lmsAdmin);
        $path = $this->seedOperatorPath($lmsAdmin, $intro, $openClaw);
        $this->seedOperatorEnrollment($lmsAdmin, $path);
    }

    private function seedOpenClawCourse(User $lmsAdmin, User $admin): Course
    {
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

        $course = Course::query()->updateOrCreate(
            ['title' => self::RESTRICTED_COURSE_TITLE],
            [
                'user_id' => $lmsAdmin->id,
                'slug' => Str::slug(self::RESTRICTED_COURSE_TITLE).'-ops',
                'short_description' => 'Operasi harian OpenClaw: deploy, log, kill switch, konektor. Bukan lab di academy ini.',
                'long_description' => 'Course terbatas. Enrollment diberikan LMS Admin lewat Learning Path setelah Pengenalan Agen AI selesai. Kamu belajar operasi: hidupkan, amati, hentikan lalu lintas berbahaya. Bukan lab dan bukan control plane — Lesson tidak membuka konsol agen hidup.',
                'objectives' => [
                    'Menjalankan operasi harian Deployment OpenClaw',
                    'Membaca log dan membedakan gagal runtime, konektor, dan konfigurasi',
                    'Memakai kill switch dan melaporkan insiden',
                    'Menjaga isolasi tenant dan kredensial konektor',
                    'Mengetahui batas academy ini: tidak ada konsol agen hidup di Lesson',
                ],
                'prerequisites' => [
                    'Wajib lulus Pengenalan Agen AI',
                ],
                'category_id' => $category->id,
                'thumbnail_path' => $thumbnailPath,
                'status' => 'published',
                'visibility' => 'restricted',
                'difficulty_level' => 'intermediate',
                'estimated_duration_minutes' => 180,
                'is_paid' => false,
                'price' => null,
                'published_at' => now(),
                'published_by' => $admin->id,
            ]
        );

        $tag = Tag::query()->firstOrCreate(
            ['slug' => 'openclaw'],
            ['name' => 'OpenClaw']
        );
        $course->tags()->syncWithoutDetaching([$tag->id]);

        if ($this->catalogMatches($course, self::LESSON_TITLES)) {
            $this->command?->info('Restricted course catalog already current: '.self::RESTRICTED_COURSE_TITLE);

            return $course->load('sections.lessons');
        }

        $this->replaceCourseLessons($course, $this->openClawSections());
        $this->command?->info('Created restricted course: '.self::RESTRICTED_COURSE_TITLE);

        return $course->load('sections.lessons');
    }

    /**
     * @return list<array{title: string, description: string, lessons: list<array<string, mixed>>}>
     */
    private function openClawSections(): array
    {
        return [
            [
                'title' => 'Pekerjaan Operator',
                'description' => 'Apa yang Operator kerjakan, dan apa yang tidak',
                'lessons' => [
                    [
                        'title' => 'Lingkup operasi harian',
                        'description' => 'Hidupkan, restart, amati. Kebijakan tenant bukan wewenangmu.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Lingkup operasi harian', 1),
                            $this->paragraph('Operator menjalankan Deployment: hidupkan, restart, amati kesehatan. Operator tidak memutuskan kebijakan tenant dan tidak menulis skill di academy ini.'),
                            $this->heading('Pagi shift'),
                            $this->bullets([
                                'Cek status Deployment sebelum menyentuh apa pun.',
                                'Baca log 30 menit terakhir, bukan hanya lampu hijau.',
                                'Catat anomali sebelum bertindak.',
                            ]),
                            $this->paragraph('Academy ini mengajarkan urutan itu. Tombol produksi tidak ada di Lesson.'),
                        ]),
                    ],
                    [
                        'title' => 'Briefing shift Operator',
                        'description' => 'Audio briefing sebelum kamu menyentuh log.',
                        'content_type' => 'audio',
                        'duration' => 8,
                        'rich_content' => $this->doc([
                            $this->heading('Briefing shift Operator', 1),
                            $this->paragraph('Putar briefing ini di awal shift. Urutannya tetap: kesehatan, log, baru tindakan. Kill switch hanya jika merugikan, lalu laporkan.'),
                        ]),
                        'fixture' => [
                            'collection' => 'audio',
                            'filename' => 'audio-briefing-shift.mp3',
                            'mime' => 'audio/mpeg',
                            'duration' => 16,
                        ],
                    ],
                    [
                        'title' => 'Yang tidak boleh disentuh',
                        'description' => 'Kebijakan tenant, data lintas tenant, academy sebagai konsol.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Yang tidak boleh disentuh', 1),
                            $this->bullets([
                                'Jangan mengubah keputusan bisnis tenant.',
                                'Jangan mencampur data antar-tenant.',
                                'Jangan memakai academy ini sebagai konsol runtime.',
                            ]),
                            $this->quote('Kalau suatu tindakan hanya bisa dilakukan dari desktop agen hidup, itu bukan pekerjaan Lesson. Tulis runbook, jangan cari tombol di sini.'),
                            $this->paragraph('Melanggar isolasi tenant adalah kegagalan operasi, bukan “salah klik di academy”.'),
                        ]),
                    ],
                ],
            ],
            [
                'title' => 'Runtime OpenClaw',
                'description' => 'Deploy, observasi, dan runbook — masih pengajaran, bukan lab',
                'lessons' => [
                    [
                        'title' => 'Deploy dan restart',
                        'description' => 'Siklus hidup Deployment dan apa yang dicek setelah restart.',
                        'content_type' => 'text',
                        'duration' => 15,
                        'rich_content' => $this->doc([
                            $this->heading('Deploy dan restart', 1),
                            $this->paragraph('Siklus hidup Deployment: provision, restart, dan memverifikasi layanan kembali sehat sebelum menyerahkan ke tenant.'),
                            $this->heading('Setelah restart'),
                            $this->bullets([
                                'Proses hidup dan menjawab health check.',
                                'Konektor yang sebelumnya sehat masih terhubung.',
                                'Tidak ada error kredensial baru di log.',
                            ]),
                            $this->paragraph('Restart bukan hukuman. Restart tanpa cek kesehatan adalah kesalahan.'),
                        ]),
                    ],
                    [
                        'title' => 'Walkthrough observasi',
                        'description' => 'Rekaman singkat cara membaca status. Bukan konsol hidup.',
                        'content_type' => 'video',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Walkthrough observasi', 1),
                            $this->paragraph('Video ini adalah rekaman pengajaran: apa yang dilihat Operator pada layar observasi. Ini bukan sesi ke Deployment hidup, dan memutarnya tidak men-deploy apa pun.'),
                        ]),
                        'fixture' => [
                            'collection' => 'video',
                            'filename' => 'video-observasi-openclaw.mp4',
                            'mime' => 'video/mp4',
                            'duration' => 8,
                        ],
                    ],
                    [
                        'title' => 'Runbook kesehatan konektor',
                        'description' => 'PDF runbook: bedakan gagal runtime, konektor, dan konfigurasi.',
                        'content_type' => 'document',
                        'duration' => 15,
                        'rich_content' => $this->doc([
                            $this->heading('Runbook kesehatan konektor', 1),
                            $this->paragraph('Baca PDF. Jangan lompat ke kill switch sebelum kamu bisa menamai jenis kegagalan.'),
                        ]),
                        'pdf' => [
                            'filename' => 'runbook-kesehatan-konektor.pdf',
                            'title' => 'Runbook Kesehatan Konektor OpenClaw',
                            'paragraphs' => [
                                'Tujuan runbook: membedakan tiga kelas kegagalan sebelum bertindak. Jangan restart buta. Jangan kill switch karena lampu kuning.',
                                '1. Gagal runtime: proses tidak hidup, health check gagal, panic di log. Tindakan: restart sesuai prosedur, verifikasi health, catat waktu pulih.',
                                '2. Gagal konektor: runtime hidup, satu kanal (pesan, kalender, berkas) error. Tindakan: cek kredensial dan kuota kanal itu, jangan menyentuh kanal lain.',
                                '3. Gagal konfigurasi tenant: runtime dan konektor hidup, perilaku salah karena kebijakan atau prompt. Tindakan: eskalasi ke pengelola tenant. Operator tidak menulis ulang kebijakan di academy.',
                                'Kredensial dan sesi konektor milik tenant. Jangan menyalinnya ke tiket publik. Jangan menempelkannya di percakapan Tutor.',
                                'Isolasi: satu VPS, satu memori, satu tenant. Kebocoran lintas tenant adalah insiden, bukan catatan biasa.',
                                'Academy ini tidak menjalankan langkah di atas. Runbook ini dibaca, lalu dikerjakan di luar EnterLMS.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Insiden',
                'description' => 'Kill switch, isolasi, dan tabletop',
                'lessons' => [
                    [
                        'title' => 'Kill switch',
                        'description' => 'Memotong lalu lintas berbahaya, lalu melapor.',
                        'content_type' => 'text',
                        'duration' => 15,
                        'rich_content' => $this->doc([
                            $this->heading('Kill switch', 1),
                            $this->paragraph('Kill switch memotong lalu lintas berbahaya per konektor atau per tenant. Pakai ketika merugikan, lalu laporkan. Ini wewenang Operator, bukan Tenant Admin.'),
                            $this->heading('Kapan'),
                            $this->bullets([
                                'Agen mengirim pesan yang merugikan manusia atau tenant.',
                                'Konektor bocor ke tujuan yang salah.',
                                'Loop tidak berhenti dan memakan kuota atau data.',
                            ]),
                            $this->heading('Setelah'),
                            $this->paragraph('Tulis: apa yang dipotong, kapan, siapa yang memutuskan, bukti di log. Jangan hidupkan lagi sebelum penyebabnya disebut.'),
                            $this->quote('Kill switch di academy ini adalah pengajaran. Tombol produksi tidak ada di Lesson.'),
                        ]),
                    ],
                    [
                        'title' => 'Kredensial, konektor, isolasi tenant',
                        'description' => 'Apa yang milik tenant, dan apa yang gagal operasi.',
                        'content_type' => 'text',
                        'duration' => 15,
                        'rich_content' => $this->doc([
                            $this->heading('Kredensial, konektor, isolasi tenant', 1),
                            $this->paragraph('Kredensial dan sesi konektor milik tenant. Isolasi VPS dan memori bukan opsi — kebocoran lintas tenant adalah kegagalan operasi.'),
                            $this->bullets([
                                'Jangan memakai satu kredensial untuk dua tenant.',
                                'Jangan men-debug tenant A dengan data tenant B.',
                                'Jangan menaruh rahasia di Conversation Tutor.',
                            ]),
                            $this->paragraph('Kalau isolasi rusak, hentikan lalu lintas, laporkan, jangan “perbaiki diam-diam”.'),
                        ]),
                    ],
                    [
                        'title' => 'Review insiden (tabletop)',
                        'description' => 'Sesi Zoom tabletop. Bukan menekan kill switch sungguhan.',
                        'content_type' => 'conference',
                        'duration' => 25,
                        'conference_url' => self::INCIDENT_REVIEW_URL,
                        'conference_type' => 'zoom',
                        'rich_content' => $this->doc([
                            $this->heading('Review insiden (tabletop)', 1),
                            $this->paragraph('Ini latihan di atas kertas dan rapat. Kamu membahas skenario, bukan memotong lalu lintas produksi dari academy.'),
                            $this->paragraph('Tautan Zoom adalah demo. Kartu konferensi menunjukkan bentuk Lesson ini: join, bukan lab.'),
                        ]),
                    ],
                    [
                        'title' => 'Ringkasan wewenang Operator',
                        'description' => 'Apa yang boleh, apa yang tidak, dan di mana academy berhenti.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->doc([
                            $this->heading('Ringkasan wewenang Operator', 1),
                            $this->bullets([
                                'Boleh: deploy, restart, baca log, kill switch, laporkan.',
                                'Tidak boleh: ubah kebijakan tenant, campur data, pakai academy sebagai konsol.',
                                'Selesai Path ini tidak membuatmu pemilik runtime. Itu pekerjaan di luar EnterLMS.',
                            ]),
                            $this->paragraph('Kerjakan ujian akhir. Esai dinilai LMS Admin lewat Grade Proposal.'),
                        ]),
                    ],
                ],
            ],
        ];
    }

    private function seedFinalExam(Course $course, User $author): void
    {
        if ($course->assessments()->where('title', self::FINAL_EXAM_TITLE)->exists()) {
            return;
        }

        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'user_id' => $author->id,
            'title' => self::FINAL_EXAM_TITLE,
            'slug' => 'ujian-akhir-administrasi-agen-openclaw',
            'description' => 'Ujian wajib Operator. Esai menunggu LMS Admin.',
            'instructions' => "Jawab dari runbook, bukan dari kebiasaan klik. Pilihan ganda dinilai otomatis. Esai menjadi Grade Proposal.\n\nJangan menuliskan kredensial sungguhan.",
            'status' => 'published',
            'passing_score' => 75,
            'max_attempts' => 2,
            'is_required' => true,
            'time_limit_minutes' => 40,
            'allow_review' => true,
            'show_correct_answers' => false,
            'published_at' => now(),
            'published_by' => $author->id,
        ]);

        $this->createMultipleChoice($assessment, 'Kapan kill switch boleh dipakai?', [
            ['option_text' => 'Ketika lalu lintas merugikan, lalu Operator wajib laporkan', 'is_correct' => true],
            ['option_text' => 'Setiap kali log berwarna kuning', 'is_correct' => false],
            ['option_text' => 'Ketika Learner publik selesai Pengenalan Agen AI', 'is_correct' => false],
            ['option_text' => 'Dari dalam Lesson sebagai ganti restart', 'is_correct' => false],
        ], 1);

        $this->createTrueFalse(
            $assessment,
            'Lesson di academy ini adalah konsol untuk runtime OpenClaw hidup.',
            false,
            2,
        );

        $this->createMultipleChoice($assessment, 'Siapa pemilik kredensial dan sesi konektor?', [
            ['option_text' => 'Tenant', 'is_correct' => true],
            ['option_text' => 'Tutor', 'is_correct' => false],
            ['option_text' => 'Learner publik yang lulus pengenalan', 'is_correct' => false],
            ['option_text' => 'Siapa pun yang membuka PDF runbook', 'is_correct' => false],
        ], 3);

        $this->createEssay(
            $assessment,
            'Kapan kamu memakai kill switch, dan apa yang kamu laporkan setelahnya?',
            'Lulus jika menyebut: merugikan sebagai syarat; memotong per konektor atau tenant; mencatat apa/kapan/siapa/bukti log; tidak menghidupkan ulang sebelum penyebab disebut; tidak mengira Lesson adalah tombol produksi.',
            4,
        );
    }

    private function seedOperatorPath(User $lmsAdmin, Course $intro, Course $openClaw): LearningPath
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
            'created_by' => $lmsAdmin->id,
            'updated_by' => $lmsAdmin->id,
            'is_published' => true,
            'visibility' => 'restricted',
            'published_at' => now(),
            'estimated_duration' => 300,
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
}
