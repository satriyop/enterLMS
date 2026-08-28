<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Tag;
use App\Models\User;
use App\Services\SeederThumbnailGenerator;
use Database\Seeders\Concerns\BuildsAcademyLessonContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds a realistic free-flow demo for local/manual testing.
 *
 * Covers the core LMS path without payment:
 * users → free published course with every Lesson form → required assessment.
 *
 * Run alone:
 *   php artisan db:seed --class=FreeFlowDemoSeeder
 *
 * Default password for all demo accounts: password
 */
class FreeFlowDemoSeeder extends Seeder
{
    use BuildsAcademyLessonContent;

    public const DEMO_PASSWORD = 'password';

    public const FREE_COURSE_TITLE = 'Pengenalan Agen AI';

    public const INTRO_QUIZ_TITLE = 'Kuis Wajib Pengenalan Agen AI';

    public const YOUTUBE_AGENTS_EXPLAINED = 'https://www.youtube.com/watch?v=d0wUM8hIaxE';

    public const OFFICE_HOURS_URL = 'https://meet.google.com/ent-rlms-hrs';

    /**
     * @var list<string>
     */
    public const LESSON_TITLES = [
        'Selamat Datang di EnterLMS',
        'Cara Menyelesaikan Pelajaran',
        'Agen yang bekerja untuk manusia',
        'Melihat agen bekerja',
        'Ulangan singkat: agen versus chatbot',
        'Tools, memori, dan batas',
        'Lembar glosarium agen',
        'Academy ini bukan control plane',
        'Office hours pengenalan',
    ];

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
        $this->seedRequiredQuiz($course, $users['lms_admin']);

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
        $thumbnailPath = app(SeederThumbnailGenerator::class)->generate(
            self::FREE_COURSE_TITLE,
            'courses/thumbnails',
            'free-flow-demo-orientation.jpg'
        );

        $course = Course::query()->updateOrCreate(
            ['title' => self::FREE_COURSE_TITLE],
            [
                'user_id' => $author->id,
                'slug' => Str::slug(self::FREE_COURSE_TITLE).'-demo',
                'short_description' => 'Kursus gratis untuk memahami apa itu agen AI, dan apa yang tidak kamu operasikan di academy ini.',
                'long_description' => 'Pengenalan terbuka untuk siapa pun. Tidak ada asumsi kamu menjalankan agen di produksi. Kamu akan mengenal agen AI — tujuan, alat, dan loop — lalu batas academy ini: EnterLMS bukan control plane, dan Lesson bukan konsol agen hidup.',
                'objectives' => [
                    'Memahami apa itu agen AI dalam bahasa sehari-hari',
                    'Membedakan agen, chatbot, dan loop alat',
                    'Membedakan belajar di academy dengan mengoperasikan agen hidup',
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
                'estimated_duration_minutes' => 120,
                'is_paid' => false,
                'price' => null,
                'published_at' => now(),
                'published_by' => $admin->id,
            ]
        );

        $agentTag = Tag::query()->firstOrCreate(
            ['slug' => 'agen-ai'],
            ['name' => 'Agen AI']
        );
        $course->tags()->syncWithoutDetaching([$agentTag->id]);

        if ($this->catalogMatches($course, self::LESSON_TITLES)) {
            $this->command?->info('Open course catalog already current: '.self::FREE_COURSE_TITLE);

            return $course->load('sections.lessons');
        }

        $this->replaceCourseLessons($course, $this->introSections());

        return $course->load('sections.lessons');
    }

    /**
     * @return list<array{title: string, description: string, lessons: list<array<string, mixed>>}>
     */
    private function introSections(): array
    {
        return [
            [
                'title' => 'Memulai di EnterLMS',
                'description' => 'Orientasi academy dan cara menyelesaikan kursus',
                'lessons' => [
                    [
                        'title' => 'Selamat Datang di EnterLMS',
                        'description' => 'Apa academy ini, siapa yang belajar di sini, dan apa yang bukan pekerjaan academy.',
                        'content_type' => 'text',
                        'duration' => 8,
                        'is_free_preview' => true,
                        'rich_content' => $this->doc([
                            $this->heading('Selamat Datang di EnterLMS', 1),
                            $this->paragraph('EnterLMS adalah academy untuk orang yang menjalankan dan membangun keluarga produk AI. Ini bukan sekolah AI generik. Ini bukan tempat men-deploy agen.'),
                            $this->paragraph('Kamu masuk sebagai Learner. LMS Admin — di fase ini hanya pendiri academy — yang menerbitkan Course, memberikan Enrollment ke Course terbatas, dan mengunci nilai.'),
                            $this->heading('Apa yang akan kamu lakukan'),
                            $this->bullets([
                                'Membaca, menonton, dan mendengarkan Lesson — teks, video, audio, dokumen, YouTube, dan konferensi.',
                                'Bertanya pada Tutor tentang Lesson yang sedang kamu buka. Tutor tidak menyelesaikan Lesson untukmu.',
                                'Menyelesaikan kuis wajib. Jawaban singkat dinilai LMS Admin, bukan langsung lulus.',
                            ]),
                            $this->quote('Selesaikan setiap Lesson untuk menandai progres. Sertifikat menunggu penyelesaian yang nyata, termasuk kuis wajib.'),
                        ]),
                    ],
                    [
                        'title' => 'Cara Menyelesaikan Pelajaran',
                        'description' => 'Progres, Enrollment, Tutor, dan sertifikat.',
                        'content_type' => 'text',
                        'duration' => 8,
                        'rich_content' => $this->doc([
                            $this->heading('Cara Menyelesaikan Pelajaran', 1),
                            $this->paragraph('Buka setiap Lesson secara berurutan. Progres tersimpan otomatis ketika kamu membaca halaman, memutar media, atau menandai selesai.'),
                            $this->heading('Tutor'),
                            $this->paragraph('Pada Lesson yang terikat Enrollment, kamu bisa bertanya pada Tutor. Percakapan tersimpan. Bicara tidak menyelesaikan Lesson dan tidak menerbitkan Course.'),
                            $this->heading('Penilaian'),
                            $this->bullets([
                                'Pilihan ganda dan benar/salah dinilai otomatis.',
                                'Jawaban singkat tanpa kunci pasti menjadi Grade Proposal — LMS Admin yang menerima atau menolak.',
                                'Kuis pengenalan ini wajib. Tanpa lulus, sertifikat tidak terbit.',
                            ]),
                            $this->paragraph('Preview Lesson bisa dibuka tanpa Enrollment. Tutor tidak hadir di preview.'),
                        ]),
                    ],
                ],
            ],
            [
                'title' => 'Apa itu Agen AI',
                'description' => 'Pengenalan tanpa asumsi kamu punya runtime',
                'lessons' => [
                    [
                        'title' => 'Agen yang bekerja untuk manusia',
                        'description' => 'Tujuan, alat, dan loop — bukan chatbot.',
                        'content_type' => 'text',
                        'duration' => 15,
                        'rich_content' => $this->doc([
                            $this->heading('Agen yang bekerja untuk manusia', 1),
                            $this->paragraph('Agen AI adalah program yang menerima tujuan, memakai alat, dan bertindak berulang sampai tugas selesai atau dibatasi. Manusia memberi tujuan dan batas. Agen yang memilih langkah.'),
                            $this->heading('Bukan chatbot'),
                            $this->paragraph('Chatbot menjawab dalam percakapan. Agen merencanakan, memanggil alat, melihat hasil, lalu memutuskan langkah berikutnya. Satu jawaban bukan pekerjaan agen.'),
                            $this->heading('Tiga bagian yang selalu ada'),
                            $this->bullets([
                                'Model — menalar tujuan dan memilih langkah.',
                                'Alat — tangan agen: API, berkas, pencarian, konektor.',
                                'Loop — amati, bertindak, amati lagi, sampai berhenti.',
                            ]),
                            $this->quote('Di keluarga produk ini, agen dijalankan sebagai layanan terkelola. Kamu tidak menginstall chatbot di laptop agar “jadi agen”.'),
                            $this->paragraph('Kursus ini tidak meminta kamu menjalankan apa pun. Cukup pahami bentuknya, lalu pahami batas academy.'),
                        ]),
                    ],
                    [
                        'title' => 'Melihat agen bekerja',
                        'description' => 'Video singkat Google Cloud: agen versus chatbot.',
                        'content_type' => 'youtube',
                        'duration' => 10,
                        'youtube_url' => self::YOUTUBE_AGENTS_EXPLAINED,
                        'rich_content' => $this->doc([
                            $this->heading('Melihat agen bekerja', 1),
                            $this->paragraph('Tonton penjelasan singkat Google Cloud. Bandingkan dengan Lesson sebelumnya: model, alat, dan koordinasi untuk mencapai tujuan — bukan hanya membalas chat.'),
                            $this->paragraph('Video ini bukan lab. Setelah selesai, kamu tetap di academy. Tidak ada konsol yang terbuka.'),
                        ]),
                    ],
                    [
                        'title' => 'Ulangan singkat: agen versus chatbot',
                        'description' => 'Audio recap untuk mengunci perbedaan agen dan chatbot.',
                        'content_type' => 'audio',
                        'duration' => 8,
                        'rich_content' => $this->doc([
                            $this->heading('Ulangan singkat', 1),
                            $this->paragraph('Putar rekaman ini sekali. Kalau kamu hanya ingat satu kalimat: agen menerima tujuan, memakai alat, dan bekerja dalam loop. Chatbot hanya menjawab.'),
                        ]),
                        'fixture' => [
                            'collection' => 'audio',
                            'filename' => 'audio-recap-agen.mp3',
                            'mime' => 'audio/mpeg',
                            'duration' => 19,
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Anatomi, tanpa runtime',
                'description' => 'Tools, memori, dan batas wewenang — masih di atas kertas',
                'lessons' => [
                    [
                        'title' => 'Tools, memori, dan batas',
                        'description' => 'Apa yang agen boleh sentuh, dan siapa yang memutuskan batas itu.',
                        'content_type' => 'text',
                        'duration' => 15,
                        'rich_content' => $this->doc([
                            $this->heading('Tools, memori, dan batas', 1),
                            $this->paragraph('Alat membuat agen berguna dan berbahaya. Tanpa alat, ia hanya berbicara. Dengan alat, ia bisa membaca berkas, memanggil API, atau mengirim pesan.'),
                            $this->heading('Memori'),
                            $this->paragraph('Memori kerja adalah percakapan dan hasil alat di satu tugas. Memori jangka panjang adalah catatan yang sengaja disimpan. Keduanya milik tenant, bukan milik academy.'),
                            $this->heading('Batas yang manusia tulis'),
                            $this->bullets([
                                'Tujuan — apa yang boleh dikejar.',
                                'Alat — mana yang diizinkan.',
                                'Stop — kapan loop harus berhenti, termasuk kill switch di sisi operasi.',
                            ]),
                            $this->paragraph('Menulis batas itu pekerjaan Operator dan pengelola tenant di produk runtime. Bukan pekerjaan Lesson. Academy ini mengajarkan kosakatanya.'),
                        ]),
                    ],
                    [
                        'title' => 'Lembar glosarium agen',
                        'description' => 'Satu halaman istilah yang dipakai academy ini.',
                        'content_type' => 'document',
                        'duration' => 10,
                        'rich_content' => $this->doc([
                            $this->heading('Lembar glosarium agen', 1),
                            $this->paragraph('Unduh atau baca PDF ini. Istilah di dalamnya adalah bahasa academy: Learner, Tutor, Enrollment, Open Course, Restricted Course. Bukan bahasa control plane.'),
                        ]),
                        'pdf' => [
                            'filename' => 'glosarium-agen-ai.pdf',
                            'title' => 'Glosarium Pengenalan Agen AI',
                            'paragraphs' => [
                                'Agen AI: program yang menerima tujuan, memakai alat, dan bertindak berulang. Bukan chatbot yang hanya menjawab.',
                                'Alat (tool): API, berkas, pencarian, atau konektor yang dipanggil agen di dalam loop.',
                                'Loop: amati, bertindak, amati lagi, sampai tujuan tercapai atau batas menghentikannya.',
                                'EnterLMS: academy. Tempat belajar. Bukan control plane dan bukan konsol runtime.',
                                'Tutor: guru di Lesson pada Enrollment. Tidak men-deploy agen, tidak menyelesaikan Lesson, tidak mengunci nilai.',
                                'LMS Agent: klien di luar academy yang memanggil MCP. Pekerjaan berbeda dari Tutor, token berbeda.',
                                'OpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas, bukan tombol yang kamu tekan di Lesson.',
                                'Open Course: katalog publik. Learner boleh membuat Enrollment sendiri. v1: Pengenalan Agen AI.',
                                'Restricted Course: tersembunyi dari katalog publik. LMS Admin yang memberikan Enrollment. v1: Administrasi Agen OpenClaw.',
                                'Lesson: satu unit konten. Bentuknya teks, video, audio, dokumen, YouTube, atau konferensi. Bukan lab.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Batas academy',
                'description' => 'Apa yang tidak kamu operasikan di sini',
                'lessons' => [
                    [
                        'title' => 'Academy ini bukan control plane',
                        'description' => 'Selesai kursus terbuka tidak membuka produksi.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Academy ini bukan control plane', 1),
                            $this->paragraph('EnterLMS mengajarkan. Ia tidak men-deploy agen. Ia tidak membuka konsol runtime di dalam Lesson. Mengoperasikan agen hidup bukan pekerjaan academy ini.'),
                            $this->heading('Dua Course, dua pintu'),
                            $this->bullets([
                                'Pengenalan Agen AI — Open Course, gratis, siapa pun boleh enroll.',
                                'Administrasi Agen OpenClaw — Restricted Course. LMS Admin yang memberikan Enrollment lewat Learning Path.',
                            ]),
                            $this->paragraph('Menyelesaikan kursus ini tidak membuka Course terbatas. Tidak ada “lulus lalu dapat akses produksi”.'),
                            $this->quote('Kalau kamu butuh menjalankan OpenClaw, itu di luar academy. Tutor di sini akan bilang begitu, bukan membuka desktop agen.'),
                        ]),
                    ],
                    [
                        'title' => 'Office hours pengenalan',
                        'description' => 'Sesi tanya jawab manusia. Bukan konsol agen.',
                        'content_type' => 'conference',
                        'duration' => 20,
                        'conference_url' => self::OFFICE_HOURS_URL,
                        'conference_type' => 'google_meet',
                        'rich_content' => $this->doc([
                            $this->heading('Office hours pengenalan', 1),
                            $this->paragraph('Ini pertemuan live dengan LMS Admin. Bawa pertanyaan tentang Lesson. Jangan harap sesi ini menjadi lab runtime.'),
                            $this->paragraph('Tautan di kartu adalah Google Meet demo. Kalau meeting belum dimulai, kartu tetap menjelaskan bentuk Lesson konferensi.'),
                        ]),
                    ],
                ],
            ],
        ];
    }

    private function seedRequiredQuiz(Course $course, User $author): void
    {
        if ($course->assessments()->where('title', self::INTRO_QUIZ_TITLE)->exists()) {
            return;
        }

        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'user_id' => $author->id,
            'title' => self::INTRO_QUIZ_TITLE,
            'slug' => 'kuis-wajib-pengenalan-agen-ai',
            'description' => 'Kuis wajib. Lulus ini bersama seluruh Lesson sebelum sertifikat terbit.',
            'instructions' => "Baca setiap soal. Pilihan ganda dan benar/salah dinilai otomatis. Jawaban singkat menunggu LMS Admin.\n\nIni bukan ujian operasi runtime.",
            'status' => 'published',
            'passing_score' => 70,
            'max_attempts' => 3,
            'is_required' => true,
            'time_limit_minutes' => 20,
            'allow_review' => true,
            'show_correct_answers' => true,
            'published_at' => now(),
            'published_by' => $author->id,
        ]);

        $this->createMultipleChoice($assessment, 'Apa yang membedakan agen AI dari chatbot?', [
            ['option_text' => 'Agen menerima tujuan, memakai alat, dan bekerja dalam loop', 'is_correct' => true],
            ['option_text' => 'Agen hanya menjawab lebih panjang daripada chatbot', 'is_correct' => false],
            ['option_text' => 'Agen adalah merek chatbot yang dipasang di laptop', 'is_correct' => false],
            ['option_text' => 'Agen adalah Tutor di academy ini', 'is_correct' => false],
        ], 1);

        $this->createTrueFalse(
            $assessment,
            'EnterLMS adalah control plane untuk men-deploy agen OpenClaw.',
            false,
            2,
        );

        $this->createMultipleChoice($assessment, 'Apa yang terjadi jika Learner publik menyelesaikan Pengenalan Agen AI?', [
            ['option_text' => 'Tidak otomatis membuka Course terbatas; LMS Admin yang memberi Enrollment', 'is_correct' => true],
            ['option_text' => 'Akses produksi OpenClaw terbuka otomatis', 'is_correct' => false],
            ['option_text' => 'Tutor men-deploy agen atas nama Learner', 'is_correct' => false],
            ['option_text' => 'Lesson berubah menjadi konsol runtime', 'is_correct' => false],
        ], 3);

        $this->createManualShortAnswer(
            $assessment,
            'Sebutkan satu hal yang tidak kamu operasikan di EnterLMS.',
            4,
        );
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
        $this->command->info("Course: {$course->title} (id={$course->id}, lessons={$lessonCount}, free, all lesson forms)");
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
        $this->command->line('  3. Selesaikan semua pelajaran (teks, YouTube, audio, PDF, konferensi)');
        $this->command->line('  4. Kerjakan kuis wajib — jawaban singkat menunggu LMS Admin');
        $this->command->line('  5. Buka /certificates untuk unduh sertifikat setelah lulus');
        $this->command->newLine();
    }
}
