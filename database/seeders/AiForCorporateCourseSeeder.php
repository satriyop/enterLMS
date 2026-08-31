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
 * Open catalog Course for corporate leadership: AI adoption, not a live lab.
 *
 *   php artisan db:seed --class=AiForCorporateCourseSeeder
 */
class AiForCorporateCourseSeeder extends Seeder
{
    use BuildsAcademyLessonContent;

    public const COURSE_TITLE = 'AI untuk Korporat';

    public const QUIZ_TITLE = 'Kuis Wajib AI untuk Korporat';

    /**
     * @var list<string>
     */
    public const LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'Bukan kursus men-deploy agen',
        'Apa artinya adopsi AI di perusahaan',
        'Bukan beli chatbot lalu selesai',
        'Tiga meja: operasional, tata kelola, SDM',
        'Data karyawan dan pelanggan',
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
            ['slug' => 'adopsi-ai-korporat'],
            [
                'name' => 'Adopsi AI Korporat',
                'description' => 'Kursus terbuka untuk pimpinan yang memutuskan adopsi AI di perusahaan.',
                'order' => 4,
            ]
        );
    }

    private function seedCourse(User $admin, Category $category): Course
    {
        $thumbnailPath = app(SeederThumbnailGenerator::class)->generate(
            self::COURSE_TITLE,
            'courses/thumbnails',
            'ai-untuk-korporat.jpg'
        );

        $course = Course::query()->updateOrCreate(
            ['title' => self::COURSE_TITLE],
            [
                'user_id' => $admin->id,
                'slug' => Str::slug(self::COURSE_TITLE),
                'short_description' => 'Untuk direksi, HR, manajer, dan komisaris yang ingin memahami adopsi AI di perusahaan — tanpa menjalankan agen hidup.',
                'long_description' => 'Kursus terbuka dan gratis. Kamu tidak perlu jadi teknisi. Kamu akan membedakan tren, chatbot, dan adopsi: siapa yang memutuskan, data siapa yang dipakai, risiko apa yang harus disebut di rapat direksi, dan apa yang tidak dimulai di minggu pertama. Lesson ini bukan konsol. Menyelesaikan kursus ini tidak membuka Course terbatas.',
                'objectives' => [
                    'Menjelaskan adopsi AI perusahaan dalam bahasa pimpinan, bukan bahasa vendor',
                    'Membedakan operasional, tata kelola, dan SDM',
                    'Menyebut risiko: data pelanggan, tenaga kerja, ketergantungan vendor',
                    'Menyusun pertanyaan untuk rapat direksi sebelum membeli tools',
                ],
                'prerequisites' => [
                    'Tidak ada prasyarat teknis. Ditujukan untuk pimpinan yang memutuskan, bukan yang men-deploy.',
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
            ['slug' => 'korporat'],
            ['name' => 'Korporat']
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
                'title' => 'Pimpinan yang memutuskan',
                'description' => 'Direksi, HR, manajer, komisaris — satu Course, pertanyaan berbeda',
                'lessons' => [
                    [
                        'title' => 'Untuk siapa kursus ini',
                        'description' => 'Pimpinan perusahaan yang harus bicara AI di rapat, bukan di server.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'is_free_preview' => true,
                        'rich_content' => $this->doc([
                            $this->heading('Untuk siapa kursus ini', 1),
                            $this->paragraph('Kursus ini untuk pimpinan perusahaan yang harus mengambil sikap: direksi, HR, manajer, dan komisaris. Kamu tidak perlu menulis kode. Kamu perlu bahasa yang sama sebelum ada yang membeli tools.'),
                            $this->heading('Empat kursi, satu meja'),
                            $this->bullets([
                                'Komisaris — risiko reputasi, pengawasan, dan mandat jangka panjang.',
                                'Direksi — prioritas bisnis, anggaran, dan janji ke pelanggan serta pemegang saham.',
                                'Manajer — proses kerja, kualitas output, dan waktu yang tersisa untuk menjalankan tim.',
                                'HR — tenaga kerja, penilaian kinerja, rekrutmen, dan kebijakan memakai AI di kantor.',
                            ]),
                            $this->paragraph('Kamu boleh enroll sendiri. Ini Open Course, gratis. Menyelesaikannya tidak membuka Course terbatas dan tidak memberi akses produksi.'),
                            $this->quote('Kalau yang kamu cari adalah tombol untuk menjalankan agen di perusahaan, kamu di Course yang salah. Itu bukan Lesson.'),
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
                            $this->paragraph('Pengenalan Agen AI mengajarkan apa itu agen. Kursus ini mengajarkan apa yang pimpinan perusahaan putuskan sebelum ada agen, chatbot, atau “AI enterprise” di slide vendor.'),
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
                'description' => 'Membeli chatbot bukan kebijakan perusahaan',
                'lessons' => [
                    [
                        'title' => 'Apa artinya adopsi AI di perusahaan',
                        'description' => 'Keputusan berulang: kerja, data, vendor, dan wewenang.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Apa artinya adopsi AI di perusahaan', 1),
                            $this->paragraph('Adopsi AI di perusahaan bukan satu proyek IT. Itu rangkaian keputusan: proses kerja mana yang boleh dibantu, data siapa yang boleh masuk ke vendor, siapa yang menandatangani, dan kapan berhenti.'),
                            $this->heading('Tiga pertanyaan yang selalu kembali'),
                            $this->bullets([
                                'Untuk siapa? Pelanggan, karyawan, operasi internal, atau pimpinan — masing-masing beda risiko.',
                                'Dengan data apa? Transkrip rapat, data pelanggan, kontrak, penilaian kinerja.',
                                'Siapa yang bertanggung jawab jika salah? Manajer, direksi, HR, atau komisaris.',
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
                            $this->paragraph('Chatbot menjawab percakapan. Agen menerima tujuan, memakai alat, dan bekerja dalam loop. Kebijakan perusahaan memutuskan mana yang boleh masuk ke proses kerja dan ke data pelanggan.'),
                            $this->paragraph('Vendor sering menjual ketiganya sebagai satu “AI enterprise”. Tugas pimpinan adalah memisahkannya sebelum tanda tangan.'),
                            $this->quote('Yang kamu adopsi bukan merek. Yang kamu adopsi adalah wewenang: siapa boleh memakai alat apa, pada data siapa, sampai kapan.'),
                        ]),
                    ],
                ],
            ],
            [
                'title' => 'Tiga meja',
                'description' => 'Operasional, tata kelola, SDM — jangan dicampur di satu SK',
                'lessons' => [
                    [
                        'title' => 'Tiga meja: operasional, tata kelola, SDM',
                        'description' => 'Manajer, direksi, dan HR tidak duduk di kursi yang sama.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Tiga meja', 1),
                            $this->paragraph('Perusahaan yang mencampur tiga meja ini biasanya membeli dulu, menyesal kemudian.'),
                            $this->heading('Operasional'),
                            $this->paragraph('Manajer: proses mana yang boleh dibantu AI, apa yang tetap harus ditinjau manusia, apa yang dilarang otomatis. Ini desain kerja, bukan tiket helpdesk.'),
                            $this->heading('Tata kelola'),
                            $this->paragraph('Direksi dan komisaris: anggaran, kontrak vendor, privasi, reputasi. Siapa yang boleh menandatangani data pelanggan ke pihak ketiga.'),
                            $this->heading('SDM'),
                            $this->paragraph('HR: kebijakan karyawan memakai AI, penilaian kinerja, rekrutmen, dan apa yang terjadi pada peran yang berubah. Bukan pekerjaan kursus ini untuk men-deploy agen, dan bukan tombol di Lesson.'),
                        ]),
                    ],
                    [
                        'title' => 'Data karyawan dan pelanggan',
                        'description' => 'Siapa yang punya, siapa yang boleh meminjamkan ke vendor.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->doc([
                            $this->heading('Data karyawan dan pelanggan', 1),
                            $this->paragraph('Setiap “AI untuk perusahaan” yang menyentuh kontrak, identitas, rekaman rapat, atau data pelanggan adalah keputusan data. Bukan keputusan fitur.'),
                            $this->bullets([
                                'Data pelanggan — kontrak, riwayat, identitas. Tidak keluar ke vendor tanpa dasar.',
                                'Data karyawan — penilaian, gaji, rekaman rapat. Bukan bahan latihan model tanpa persetujuan.',
                                'Rahasia dagang — proposal, harga, desain. Shadow AI di laptop staf adalah kebocoran yang sudah terjadi.',
                            ]),
                            $this->paragraph('Direksi dan komisaris yang tidak bisa menyebut data mana yang keluar perusahaan, belum siap adopsi. Mereka baru siap demo.'),
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
                        'description' => 'Data pelanggan, tenaga kerja, ketergantungan vendor.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Risiko yang pimpinan harus sebutkan', 1),
                            $this->bullets([
                                'Data pelanggan — data yang tidak bisa ditarik kembali dari vendor.',
                                'Tenaga kerja — “pakai AI” tanpa kebijakan SDM adalah PHK tersembunyi atau kerja tambahan tanpa nama.',
                                'Ketergantungan vendor — perusahaan tidak bisa pindah tanpa kehilangan riwayat.',
                                'Shadow AI — staf sudah memakai tools pribadi di data perusahaan sebelum ada kebijakan.',
                            ]),
                            $this->paragraph('Menyebut risiko bukan menolak AI. Menyembunyikan risiko adalah yang membuat komisaris kaget di tahun kedua.'),
                        ]),
                    ],
                    [
                        'title' => 'Lembar keputusan pimpinan',
                        'description' => 'Satu halaman untuk rapat direksi dan komisaris.',
                        'content_type' => 'document',
                        'duration' => 8,
                        'rich_content' => $this->doc([
                            $this->heading('Lembar keputusan pimpinan', 1),
                            $this->paragraph('Unduh atau baca PDF ini sebelum rapat. Bukan checklist belanja tools. Daftar keputusan yang harus ada pemiliknya.'),
                        ]),
                        'pdf' => [
                            'filename' => 'lembar-keputusan-ai-korporat.pdf',
                            'title' => 'Lembar Keputusan AI untuk Korporat',
                            'paragraphs' => [
                                'Kursus: AI untuk Korporat. Open Course, gratis. Bukan lab, bukan konsol.',
                                'Untuk: direksi, HR, manajer, komisaris.',
                                '1. Untuk siapa AI dipakai di perusahaan ini tahun ini? Operasi, pelanggan, SDM — sebut satu prioritas.',
                                '2. Data apa yang boleh keluar ke vendor? Pelanggan, karyawan, rahasia dagang — sebut yang dilarang.',
                                '3. Siapa menandatangani? Direksi, komisaris, atau pemilik fungsi.',
                                '4. Apa kebijakan karyawan memakai AI di kerja sehari-hari? Diam adalah kebijakan terburuk.',
                                '5. Apa yang tidak dimulai minggu pertama? Deploy agen hidup, unggah seluruh arsip pelanggan, kontrak tanpa klausul data.',
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
                        'description' => 'Agenda 45 menit yang cukup untuk direksi dan komisaris.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->doc([
                            $this->heading('Mulai dari rapat, bukan dari tools', 1),
                            $this->paragraph('Undangan rapat yang hanya berisi demo vendor akan menghasilkan pembelian. Undangan yang berisi lima pertanyaan di lembar keputusan akan menghasilkan sikap.'),
                            $this->heading('Agenda singkat'),
                            $this->bullets([
                                'Sepuluh menit: satu prioritas tahun ini (bukan sepuluh).',
                                'Lima belas menit: data yang dilarang keluar.',
                                'Sepuluh menit: kebijakan karyawan — boleh, wajib deklarasi, atau dilarang di data pelanggan.',
                                'Sepuluh menit: siapa pemilik keputusan lanjutan.',
                            ]),
                            $this->paragraph('Kalau rapat berakhir tanpa pemilik, perusahaan belum adopsi. Perusahaan baru menonton slide.'),
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
                                'Menjalankan agen hidup di jaringan kantor “supaya kelihatan modern”.',
                                'Mengunggah arsip pelanggan atau karyawan ke vendor untuk “melatih AI perusahaan”.',
                                'Mewajibkan seluruh staf memakai satu merek dalam tujuh hari.',
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
            'slug' => 'kuis-wajib-ai-untuk-korporat',
            'description' => 'Kuis wajib untuk direksi, HR, dan manajer. Bukan ujian operasi runtime.',
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

        $this->createMultipleChoice($assessment, 'Siapa audiens utama kursus AI untuk Korporat?', [
            ['option_text' => 'Direksi, HR, manajer, dan komisaris yang memutuskan adopsi', 'is_correct' => true],
            ['option_text' => 'Teknisi yang men-deploy agen di produksi', 'is_correct' => false],
            ['option_text' => 'Staf yang ingin jadi operator OpenClaw', 'is_correct' => false],
            ['option_text' => 'Vendor yang menjual chatbot perusahaan', 'is_correct' => false],
        ], 1);

        $this->createTrueFalse(
            $assessment,
            'Membeli chatbot berarti perusahaan sudah mengadopsi AI.',
            false,
            2,
        );

        $this->createMultipleChoice($assessment, 'Apa yang tidak dimulai di minggu pertama adopsi?', [
            ['option_text' => 'Menjalankan agen hidup atau mengunggah arsip pelanggan ke vendor tanpa kebijakan', 'is_correct' => true],
            ['option_text' => 'Rapat pimpinan dengan pertanyaan tentang data dan wewenang', 'is_correct' => false],
            ['option_text' => 'Membaca Open Course ini', 'is_correct' => false],
            ['option_text' => 'Menyusun kebijakan karyawan tentang AI di kerja sehari-hari', 'is_correct' => false],
        ], 3);

        $this->createManualShortAnswer(
            $assessment,
            'Sebutkan satu risiko yang harus disebut direksi atau komisaris sebelum menandatangani vendor AI.',
            4,
        );
    }
}
