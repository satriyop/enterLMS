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
use Illuminate\Support\Str;

/**
 * Open catalog Course for campus leadership: AI adoption, not a live lab.
 *
 *   php artisan db:seed --class=AiForCampusCourseSeeder
 */
class AiForCampusCourseSeeder extends Seeder
{
    use BuildsAcademyLessonContent;

    public const COURSE_TITLE = 'AI untuk Kampus';

    public const QUIZ_TITLE = 'Kuis Wajib AI untuk Kampus';

    /**
     * @var list<string>
     */
    public const LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'Bukan kursus men-deploy agen',
        'Apa artinya adopsi AI di kampus',
        'Bukan beli chatbot lalu selesai',
        'Tiga meja: pengajaran, tata kelola, operasional',
        'Data mahasiswa dan dosen',
        'Risiko yang pimpinan harus sebutkan',
        'Lembar keputusan pimpinan',
        'Mulai dari rapat, bukan dari tools',
        'Apa yang tidak dimulai minggu pertama',
    ];

    public function run(): void
    {
        $lmsAdmin = User::query()->where('role', 'lms_admin')->first();

        if (! $lmsAdmin) {
            $this->command?->warn('LMS Admin not found. Run FreeFlowDemoSeeder first.');

            return;
        }

        $category = $this->ensureCategory();
        $course = $this->seedCourse($lmsAdmin, $category);
        $this->seedQuiz($course, $lmsAdmin);

        $this->command?->info('Open course ready: '.self::COURSE_TITLE);
    }

    private function ensureCategory(): Category
    {
        return Category::query()->firstOrCreate(
            ['slug' => 'adopsi-ai-kampus'],
            [
                'name' => 'Adopsi AI Kampus',
                'description' => 'Kursus terbuka untuk sivitas yang memutuskan adopsi AI di kampus.',
                'order' => 3,
            ]
        );
    }

    private function seedCourse(User $admin, Category $category): Course
    {
        $thumbnailPath = app(SeederThumbnailGenerator::class)->generate(
            self::COURSE_TITLE,
            'courses/thumbnails',
            'ai-untuk-kampus.jpg'
        );

        $course = Course::query()->updateOrCreate(
            ['title' => self::COURSE_TITLE],
            [
                'user_id' => $admin->id,
                'slug' => Str::slug(self::COURSE_TITLE),
                'short_description' => 'Untuk dekan, dosen, pemangku kepentingan, dan ketua yayasan yang ingin memahami adopsi AI di kampus — tanpa menjalankan agen hidup.',
                'long_description' => 'Kursus terbuka dan gratis. Kamu tidak perlu jadi teknisi. Kamu akan membedakan tren, chatbot, dan adopsi: siapa yang memutuskan, data siapa yang dipakai, risiko apa yang harus disebut di rapat pimpinan, dan apa yang tidak dimulai di minggu pertama. Lesson ini bukan konsol. Menyelesaikan kursus ini tidak membuka Course terbatas.',
                'objectives' => [
                    'Menjelaskan adopsi AI kampus dalam bahasa pimpinan, bukan bahasa vendor',
                    'Membedakan pengajaran, tata kelola, dan operasional',
                    'Menyebut risiko: integritas akademik, privasi, ketergantungan vendor',
                    'Menyusun pertanyaan untuk rapat pimpinan sebelum membeli tools',
                ],
                'prerequisites' => [
                    'Tidak ada prasyarat teknis. Ditujukan untuk sivitas yang memutuskan, bukan yang men-deploy.',
                ],
                'category_id' => $category->id,
                'thumbnail_path' => $thumbnailPath,
                'status' => 'published',
                'visibility' => 'public',
                'difficulty_level' => 'beginner',
                'estimated_duration_minutes' => 90,
                'is_paid' => false,
                'price' => null,
                'published_at' => now(),
                'published_by' => $admin->id,
            ]
        );

        $tag = Tag::query()->firstOrCreate(
            ['slug' => 'kampus'],
            ['name' => 'Kampus']
        );
        $course->tags()->syncWithoutDetaching([$tag->id]);

        if ($this->catalogMatches($course, self::LESSON_TITLES)) {
            $this->command?->info('Open course catalog already current: '.self::COURSE_TITLE);

            return $course->load('sections.lessons');
        }

        $this->replaceCourseLessons($course, $this->sections());

        return $course->load('sections.lessons');
    }

    /**
     * @return list<array{title: string, description: string, lessons: list<array<string, mixed>>}>
     */
    private function sections(): array
    {
        return [
            [
                'title' => 'Sivitas yang memutuskan',
                'description' => 'Dekan, dosen, pemangku kepentingan, ketua yayasan — satu Course, pertanyaan berbeda',
                'lessons' => [
                    [
                        'title' => 'Untuk siapa kursus ini',
                        'description' => 'Sivitas akademika yang harus bicara AI di rapat, bukan di server.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'is_free_preview' => true,
                        'rich_content' => $this->doc([
                            $this->heading('Untuk siapa kursus ini', 1),
                            $this->paragraph('Kursus ini untuk sivitas kampus yang harus mengambil sikap: dekan, dosen, pemangku kepentingan, dan ketua yayasan. Kamu tidak perlu menulis kode. Kamu perlu bahasa yang sama sebelum ada yang membeli tools.'),
                            $this->heading('Empat kursi, satu meja'),
                            $this->bullets([
                                'Ketua yayasan — risiko reputasi, anggaran, dan mandat jangka panjang.',
                                'Dekan / pimpinan fakultas — prioritas program, beban dosen, janji ke mahasiswa.',
                                'Dosen — ruang kelas, integritas tugas, waktu yang tersisa untuk mengajar.',
                                'Pemangku kepentingan lain — mahasiswa, orang tua, mitra industri, regulator.',
                            ]),
                            $this->paragraph('Kamu boleh enroll sendiri. Ini Open Course, gratis. Menyelesaikannya tidak membuka Course terbatas dan tidak memberi akses produksi.'),
                            $this->quote('Kalau yang kamu cari adalah tombol untuk menjalankan agen di kampus, kamu di Course yang salah. Itu bukan Lesson.'),
                        ]),
                    ],
                    [
                        'title' => 'Bukan kursus men-deploy agen',
                        'description' => 'Adopsi bukan lab. Lesson bukan konsol.',
                        'content_type' => 'text',
                        'duration' => 8,
                        'rich_content' => $this->doc([
                            $this->heading('Bukan kursus men-deploy agen', 1),
                            $this->paragraph('EnterLMS adalah academy. Tutor di sini menjelaskan. Ia tidak men-deploy agen, tidak membuka desktop runtime, dan tidak menyelesaikan Lesson untukmu.'),
                            $this->paragraph('Pengenalan Agen AI mengajarkan apa itu agen. Kursus ini mengajarkan apa yang pimpinan kampus putuskan sebelum ada agen, chatbot, atau “AI campus” di slide vendor.'),
                            $this->bullets([
                                'Tidak ada lab di Lesson.',
                                'Tidak ada akses produksi karena lulus kuis.',
                                'Restricted Course tentang operasi runtime tetap tersembunyi dari katalog ini.',
                            ]),
                        ]),
                    ],
                ],
            ],
            [
                'title' => 'Adopsi, bukan tren',
                'description' => 'Membeli chatbot bukan kebijakan kampus',
                'lessons' => [
                    [
                        'title' => 'Apa artinya adopsi AI di kampus',
                        'description' => 'Keputusan berulang: pengajaran, data, vendor, dan wewenang.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Apa artinya adopsi AI di kampus', 1),
                            $this->paragraph('Adopsi AI di kampus bukan satu proyek IT. Itu rangkaian keputusan: apa yang boleh dipakai di kelas, data siapa yang boleh masuk ke vendor, siapa yang menandatangani, dan kapan berhenti.'),
                            $this->heading('Tiga pertanyaan yang selalu kembali'),
                            $this->bullets([
                                'Untuk siapa? Mahasiswa, dosen, administrasi, atau riset — masing-masing beda risiko.',
                                'Dengan data apa? Tugas, transkrip, identitas, rekaman kuliah.',
                                'Siapa yang bertanggung jawab jika salah? Dosen, fakultas, rektorat, atau yayasan.',
                            ]),
                            $this->paragraph('Kalau rapat hanya membahas merek tools, itu belum adopsi. Itu belanja.'),
                        ]),
                    ],
                    [
                        'title' => 'Bukan beli chatbot lalu selesai',
                        'description' => 'Chatbot, agen, dan kebijakan adalah tiga benda berbeda.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->doc([
                            $this->heading('Bukan beli chatbot lalu selesai', 1),
                            $this->paragraph('Chatbot menjawab percakapan. Agen menerima tujuan, memakai alat, dan bekerja dalam loop. Kebijakan kampus memutuskan mana yang boleh masuk ke kelas dan ke data mahasiswa.'),
                            $this->paragraph('Vendor sering menjual ketiganya sebagai satu “AI kampus”. Tugas pimpinan adalah memisahkannya sebelum tanda tangan.'),
                            $this->quote('Yang kamu adopsi bukan merek. Yang kamu adopsi adalah wewenang: siapa boleh memakai alat apa, pada data siapa, sampai kapan.'),
                        ]),
                    ],
                ],
            ],
            [
                'title' => 'Tiga meja',
                'description' => 'Pengajaran, tata kelola, operasional — jangan dicampur di satu SK',
                'lessons' => [
                    [
                        'title' => 'Tiga meja: pengajaran, tata kelola, operasional',
                        'description' => 'Dosen, pimpinan, dan operator tidak duduk di kursi yang sama.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Tiga meja', 1),
                            $this->paragraph('Kampus yang mencampur tiga meja ini biasanya membeli dulu, menyesal kemudian.'),
                            $this->heading('Pengajaran'),
                            $this->paragraph('Dosen: bolehkah mahasiswa memakai AI untuk tugas, ujian, skripsi? Apa yang harus dinyatakan di silabus? Ini kebijakan akademik, bukan tiket helpdesk.'),
                            $this->heading('Tata kelola'),
                            $this->paragraph('Dekan dan yayasan: anggaran, kontrak vendor, privasi, reputasi. Siapa yang boleh menandatangani data mahasiswa ke pihak ketiga.'),
                            $this->heading('Operasional'),
                            $this->paragraph('Menjalankan sistem — log, akses, henti darurat — bukan pekerjaan kursus ini dan bukan tombol di Lesson. Kalau kampus kelak mengoperasikan agen, itu di luar academy ini.'),
                        ]),
                    ],
                    [
                        'title' => 'Data mahasiswa dan dosen',
                        'description' => 'Siapa yang punya, siapa yang boleh meminjamkan ke vendor.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->doc([
                            $this->heading('Data mahasiswa dan dosen', 1),
                            $this->paragraph('Setiap “AI untuk kampus” yang menyentuh tugas, nilai, identitas, atau rekaman kuliah adalah keputusan data. Bukan keputusan fitur.'),
                            $this->bullets([
                                'Tugas dan ujian — integritas akademik dan hak cipta mahasiswa.',
                                'Identitas dan NIM — tidak masuk ke vendor asing tanpa dasar.',
                                'Rekaman dosen — bukan bahan latihan model tanpa persetujuan.',
                            ]),
                            $this->paragraph('Ketua yayasan dan dekan yang tidak bisa menyebut data mana yang keluar kampus, belum siap adopsi. Mereka baru siap demo.'),
                        ]),
                    ],
                ],
            ],
            [
                'title' => 'Risiko dan keputusan',
                'description' => 'Yang harus disebut sebelum kontrak',
                'lessons' => [
                    [
                        'title' => 'Risiko yang pimpinan harus sebutkan',
                        'description' => 'Integritas akademik, privasi, ketergantungan vendor.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Risiko yang pimpinan harus sebutkan', 1),
                            $this->bullets([
                                'Integritas akademik — tugas dan ujian kehilangan makna jika kebijakan diam.',
                                'Privasi — data mahasiswa yang tidak bisa ditarik kembali dari vendor.',
                                'Ketergantungan vendor — kampus tidak bisa pindah tanpa kehilangan riwayat.',
                                'Beban dosen — “pakai AI” tanpa waktu desain ulang kuliah adalah kerja tambahan tersembunyi.',
                            ]),
                            $this->paragraph('Menyebut risiko bukan menolak AI. Menyembunyikan risiko adalah yang membuat yayasan kaget di tahun kedua.'),
                        ]),
                    ],
                    [
                        'title' => 'Lembar keputusan pimpinan',
                        'description' => 'Satu halaman untuk rapat dekan dan yayasan.',
                        'content_type' => 'document',
                        'duration' => 8,
                        'rich_content' => $this->doc([
                            $this->heading('Lembar keputusan pimpinan', 1),
                            $this->paragraph('Unduh atau baca PDF ini sebelum rapat. Bukan checklist belanja tools. Daftar keputusan yang harus ada pemiliknya.'),
                        ]),
                        'pdf' => [
                            'filename' => 'lembar-keputusan-ai-kampus.pdf',
                            'title' => 'Lembar Keputusan AI untuk Kampus',
                            'paragraphs' => [
                                'Kursus: AI untuk Kampus. Open Course, gratis. Bukan lab, bukan konsol.',
                                'Untuk: dekan, dosen, pemangku kepentingan, ketua yayasan.',
                                '1. Untuk siapa AI dipakai di kampus ini tahun ini? Kelas, administrasi, riset — sebut satu prioritas.',
                                '2. Data apa yang boleh keluar ke vendor? Tugas, identitas, rekaman, nilai — sebut yang dilarang.',
                                '3. Siapa menandatangani? Dekan, rektorat, atau yayasan.',
                                '4. Apa kebijakan mahasiswa memakai AI di tugas dan ujian? Diam adalah kebijakan terburuk.',
                                '5. Apa yang tidak dimulai minggu pertama? Deploy agen hidup, unggah seluruh repositori tugas, kontrak tanpa klausul data.',
                                'Adopsi: keputusan wewenang, bukan pembelian chatbot.',
                                'EnterLMS: academy. Menyelesaikan kursus ini tidak membuka Course terbatas dan tidak men-deploy apa pun.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Mulai dari sini',
                'description' => 'Rapat pimpinan sebelum tools',
                'lessons' => [
                    [
                        'title' => 'Mulai dari rapat, bukan dari tools',
                        'description' => 'Agenda 45 menit yang cukup untuk dekan dan yayasan.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->doc([
                            $this->heading('Mulai dari rapat, bukan dari tools', 1),
                            $this->paragraph('Undangan rapat yang hanya berisi demo vendor akan menghasilkan pembelian. Undangan yang berisi lima pertanyaan di lembar keputusan akan menghasilkan sikap.'),
                            $this->heading('Agenda singkat'),
                            $this->bullets([
                                'Sepuluh menit: satu prioritas tahun ini (bukan sepuluh).',
                                'Lima belas menit: data yang dilarang keluar.',
                                'Sepuluh menit: kebijakan kelas — boleh, wajib deklarasi, atau dilarang di ujian.',
                                'Sepuluh menit: siapa pemilik keputusan lanjutan.',
                            ]),
                            $this->paragraph('Kalau rapat berakhir tanpa pemilik, kampus belum adopsi. Kampus baru menonton slide.'),
                        ]),
                    ],
                    [
                        'title' => 'Apa yang tidak dimulai minggu pertama',
                        'description' => 'Daftar yang menahan FOMO tanpa menolak belajar.',
                        'content_type' => 'text',
                        'duration' => 8,
                        'rich_content' => $this->doc([
                            $this->heading('Apa yang tidak dimulai minggu pertama', 1),
                            $this->bullets([
                                'Menjalankan agen hidup di jaringan kampus “supaya kelihatan modern”.',
                                'Mengunggah arsip tugas mahasiswa ke vendor untuk “melatih AI kampus”.',
                                'Mewajibkan seluruh dosen memakai satu merek dalam tujuh hari.',
                                'Membuka konsol runtime di dalam Lesson atau LMS.',
                            ]),
                            $this->paragraph('Belajar di academy ini boleh dimulai hari ini. Mengoperasikan agen hidup tidak. Tutor di Lesson ini akan menolak membuka konsol, karena Lesson bukan lab.'),
                            $this->quote('Adopsi yang dewasa kelihatan lambat di minggu pertama, dan murah di tahun kedua.'),
                        ]),
                    ],
                ],
            ],
        ];
    }

    private function seedQuiz(Course $course, User $author): void
    {
        if ($course->assessments()->where('title', self::QUIZ_TITLE)->exists()) {
            return;
        }

        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'user_id' => $author->id,
            'title' => self::QUIZ_TITLE,
            'slug' => 'kuis-wajib-ai-untuk-kampus',
            'description' => 'Kuis wajib untuk pimpinan dan dosen. Bukan ujian operasi runtime.',
            'instructions' => "Jawab dari Lesson. Pilihan ganda dinilai otomatis. Jawaban singkat menunggu LMS Admin.\n\nIni bukan ujian men-deploy agen.",
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

        $this->createMultipleChoice($assessment, 'Siapa audiens utama kursus AI untuk Kampus?', [
            ['option_text' => 'Dekan, dosen, pemangku kepentingan, dan ketua yayasan yang memutuskan adopsi', 'is_correct' => true],
            ['option_text' => 'Teknisi yang men-deploy agen di produksi', 'is_correct' => false],
            ['option_text' => 'Mahasiswa yang ingin jadi operator OpenClaw', 'is_correct' => false],
            ['option_text' => 'Vendor yang menjual chatbot kampus', 'is_correct' => false],
        ], 1);

        $this->createTrueFalse(
            $assessment,
            'Membeli chatbot berarti kampus sudah mengadopsi AI.',
            false,
            2,
        );

        $this->createMultipleChoice($assessment, 'Apa yang tidak dimulai di minggu pertama adopsi?', [
            ['option_text' => 'Menjalankan agen hidup atau mengunggah arsip tugas mahasiswa ke vendor tanpa kebijakan', 'is_correct' => true],
            ['option_text' => 'Rapat pimpinan dengan pertanyaan tentang data dan wewenang', 'is_correct' => false],
            ['option_text' => 'Membaca Open Course ini', 'is_correct' => false],
            ['option_text' => 'Menyusun kebijakan kelas tentang AI di tugas', 'is_correct' => false],
        ], 3);

        $this->createManualShortAnswer(
            $assessment,
            'Sebutkan satu risiko yang harus disebut dekan atau ketua yayasan sebelum menandatangani vendor AI.',
            4,
        );
    }
}
