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
 * Open catalog Course for military leadership: AI adoption, not a live lab.
 *
 *   php artisan db:seed --class=AiForMilitaryCourseSeeder
 */
class AiForMilitaryCourseSeeder extends Seeder
{
    use BuildsAcademyLessonContent;

    public const COURSE_TITLE = 'AI untuk Militer';

    public const QUIZ_TITLE = 'Kuis Wajib AI untuk Militer';

    /**
     * @var list<string>
     */
    public const LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'Bukan kursus men-deploy agen',
        'Apa artinya adopsi AI di kesatuan',
        'Bukan beli chatbot lalu selesai',
        'Tiga meja: tugas, komando, personel',
        'Data personel dan kesatuan',
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
            ['slug' => 'adopsi-ai-militer'],
            [
                'name' => 'Adopsi AI Militer',
                'description' => 'Kursus terbuka untuk pimpinan kesatuan yang memutuskan adopsi AI.',
                'order' => 5,
            ]
        );
    }

    private function seedCourse(User $admin, Category $category): Course
    {
        $thumbnailPath = app(SeederThumbnailGenerator::class)->generate(
            self::COURSE_TITLE,
            'courses/thumbnails',
            'ai-untuk-militer.jpg'
        );

        $course = Course::query()->updateOrCreate(
            ['title' => self::COURSE_TITLE],
            [
                'user_id' => $admin->id,
                'slug' => Str::slug(self::COURSE_TITLE),
                'short_description' => 'Untuk komandan, perwira staf, personel, dan pimpinan kesatuan yang ingin memahami adopsi AI — tanpa menjalankan agen hidup.',
                'long_description' => 'Kursus terbuka dan gratis. Kamu tidak perlu jadi teknisi. Kamu akan membedakan tren, chatbot, dan adopsi: siapa yang memutuskan, data siapa yang dipakai, risiko apa yang harus disebut di rapat pimpinan, dan apa yang tidak dimulai di minggu pertama. Lesson ini bukan konsol. Menyelesaikan kursus ini tidak membuka Course terbatas.',
                'objectives' => [
                    'Menjelaskan adopsi AI kesatuan dalam bahasa pimpinan, bukan bahasa vendor',
                    'Membedakan tugas harian, komando, dan personel',
                    'Menyebut risiko: data kesatuan, ketergantungan vendor, shadow AI di perangkat dinas',
                    'Menyusun pertanyaan untuk rapat pimpinan sebelum membeli tools',
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
            ['slug' => 'militer'],
            ['name' => 'Militer']
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
                'description' => 'Komandan, perwira staf, personel, pimpinan kesatuan — satu Course, pertanyaan berbeda',
                'lessons' => [
                    [
                        'title' => 'Untuk siapa kursus ini',
                        'description' => 'Pimpinan kesatuan yang harus bicara AI di rapat, bukan di server.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'is_free_preview' => true,
                        'rich_content' => $this->doc([
                            $this->heading('Untuk siapa kursus ini', 1),
                            $this->paragraph('Kursus ini untuk pimpinan kesatuan yang harus mengambil sikap: komandan, perwira staf, personel, dan pimpinan di atasnya. Kamu tidak perlu menulis kode. Kamu perlu bahasa yang sama sebelum ada yang membeli tools.'),
                            $this->heading('Empat kursi, satu meja'),
                            $this->bullets([
                                'Pimpinan kesatuan — mandat, reputasi, dan tanggung jawab jangka panjang.',
                                'Komandan — prioritas tugas, beban staf, dan apa yang boleh masuk ke ruang kerja.',
                                'Perwira staf — data, koordinasi, dan apa yang tertulis di perintah harian.',
                                'Personel — prajurit dan staf yang akan memakai — atau dilarang memakai — tools di perangkat dinas.',
                            ]),
                            $this->paragraph('Kamu boleh enroll sendiri. Ini Open Course, gratis. Menyelesaikannya tidak membuka Course terbatas dan tidak memberi akses produksi.'),
                            $this->quote('Kalau yang kamu cari adalah tombol untuk menjalankan agen di kesatuan, kamu di Course yang salah. Itu bukan Lesson.'),
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
                            $this->paragraph('Pengenalan Agen AI mengajarkan apa itu agen. Kursus ini mengajarkan apa yang pimpinan kesatuan putuskan sebelum ada agen, chatbot, atau “AI militer” di slide vendor.'),
                            $this->bullets([
                                'Tidak ada lab di Lesson.',
                                'Tidak ada akses produksi karena lulus kuis.',
                                'Restricted Course tentang operasi runtime tetap tersembunyi dari katalog ini.',
                                'Kursus ini bukan pelatihan senjata, intelijen, atau operasi tempur.',
                            ]),
                        ]),
                    ],
                ],
            ],
            [
                'title' => 'Adopsi, bukan tren',
                'description' => 'Membeli chatbot bukan kebijakan kesatuan',
                'lessons' => [
                    [
                        'title' => 'Apa artinya adopsi AI di kesatuan',
                        'description' => 'Keputusan berulang: tugas, data, vendor, dan wewenang.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Apa artinya adopsi AI di kesatuan', 1),
                            $this->paragraph('Adopsi AI di kesatuan bukan satu proyek IT. Itu rangkaian keputusan: pekerjaan mana yang boleh dibantu, data siapa yang boleh masuk ke vendor, siapa yang menandatangani, dan kapan berhenti.'),
                            $this->heading('Tiga pertanyaan yang selalu kembali'),
                            $this->bullets([
                                'Untuk siapa? Administrasi, pelatihan, atau kerja staf — masing-masing beda risiko.',
                                'Dengan data apa? Daftar personel, surat, peta kerja, rekaman rapat.',
                                'Siapa yang bertanggung jawab jika salah? Staf, komandan, atau pimpinan di atasnya.',
                            ]),
                            $this->paragraph('Kalau rapat hanya membahas merek tools, itu belum adopsi. Itu belanja.'),
                        ]),
                    ],
                    [
                        'title' => 'Bukan beli chatbot lalu selesai',
                        'description' => 'Chatbot, agen, dan perintah adalah tiga benda berbeda.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->doc([
                            $this->heading('Bukan beli chatbot lalu selesai', 1),
                            $this->paragraph('Chatbot menjawab percakapan. Agen menerima tujuan, memakai alat, dan bekerja dalam loop. Perintah kesatuan memutuskan mana yang boleh masuk ke kerja staf dan ke data personel.'),
                            $this->paragraph('Vendor sering menjual ketiganya sebagai satu “AI militer”. Tugas pimpinan adalah memisahkannya sebelum tanda tangan.'),
                            $this->quote('Yang kamu adopsi bukan merek. Yang kamu adopsi adalah wewenang: siapa boleh memakai alat apa, pada data siapa, sampai kapan.'),
                        ]),
                    ],
                ],
            ],
            [
                'title' => 'Tiga meja',
                'description' => 'Tugas, komando, personel — jangan dicampur di satu perintah',
                'lessons' => [
                    [
                        'title' => 'Tiga meja: tugas, komando, personel',
                        'description' => 'Staf, komandan, dan personel tidak duduk di kursi yang sama.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Tiga meja', 1),
                            $this->paragraph('Kesatuan yang mencampur tiga meja ini biasanya membeli dulu, menyesal kemudian.'),
                            $this->heading('Tugas'),
                            $this->paragraph('Staf: administrasi, korespondensi, ringkasan rapat, pelatihan. Mana yang boleh dibantu, mana yang tetap harus ditinjau manusia. Ini desain kerja, bukan tiket helpdesk.'),
                            $this->heading('Komando'),
                            $this->paragraph('Komandan dan pimpinan: anggaran, kontrak vendor, klasifikasi, reputasi. Siapa yang boleh menandatangani data kesatuan ke pihak ketiga.'),
                            $this->heading('Personel'),
                            $this->paragraph('Kebijakan prajurit dan staf memakai AI di perangkat dinas. Bukan pekerjaan kursus ini untuk men-deploy agen, dan bukan tombol di Lesson.'),
                        ]),
                    ],
                    [
                        'title' => 'Data personel dan kesatuan',
                        'description' => 'Siapa yang punya, siapa yang boleh meminjamkan ke vendor.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->doc([
                            $this->heading('Data personel dan kesatuan', 1),
                            $this->paragraph('Setiap “AI untuk militer” yang menyentuh daftar personel, surat dinas, peta kerja, atau rekaman rapat adalah keputusan data. Bukan keputusan fitur.'),
                            $this->bullets([
                                'Data personel — identitas, riwayat, penilaian. Tidak keluar ke vendor tanpa dasar.',
                                'Surat dan perintah — bukan bahan latihan model tanpa persetujuan.',
                                'Peta kerja dan lokasi — shadow AI di perangkat dinas adalah kebocoran yang sudah terjadi.',
                            ]),
                            $this->paragraph('Komandan yang tidak bisa menyebut data mana yang keluar kesatuan, belum siap adopsi. Ia baru siap demo.'),
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
                        'description' => 'Data kesatuan, ketergantungan vendor, shadow AI.',
                        'content_type' => 'text',
                        'duration' => 12,
                        'rich_content' => $this->doc([
                            $this->heading('Risiko yang pimpinan harus sebutkan', 1),
                            $this->bullets([
                                'Data kesatuan — data yang tidak bisa ditarik kembali dari vendor.',
                                'Ketergantungan vendor — kesatuan tidak bisa pindah tanpa kehilangan riwayat.',
                                'Shadow AI — staf sudah memakai tools pribadi di perangkat dinas sebelum ada perintah.',
                                'Perintah tanpa kebijakan — “pakai AI” tanpa batas data adalah kebocoran yang dilegalkan.',
                            ]),
                            $this->paragraph('Menyebut risiko bukan menolak AI. Menyembunyikan risiko adalah yang membuat pimpinan kaget di tahun kedua.'),
                        ]),
                    ],
                    [
                        'title' => 'Lembar keputusan pimpinan',
                        'description' => 'Satu halaman untuk rapat komandan dan staf.',
                        'content_type' => 'document',
                        'duration' => 8,
                        'rich_content' => $this->doc([
                            $this->heading('Lembar keputusan pimpinan', 1),
                            $this->paragraph('Unduh atau baca PDF ini sebelum rapat. Bukan checklist belanja tools. Daftar keputusan yang harus ada pemiliknya.'),
                        ]),
                        'pdf' => [
                            'filename' => 'lembar-keputusan-ai-militer.pdf',
                            'title' => 'Lembar Keputusan AI untuk Militer',
                            'paragraphs' => [
                                'Kursus: AI untuk Militer. Open Course, gratis. Bukan lab, bukan konsol.',
                                'Untuk: komandan, perwira staf, personel, pimpinan kesatuan.',
                                '1. Untuk siapa AI dipakai di kesatuan ini tahun ini? Administrasi, pelatihan, kerja staf — sebut satu prioritas.',
                                '2. Data apa yang boleh keluar ke vendor? Personel, surat, peta kerja — sebut yang dilarang.',
                                '3. Siapa menandatangani? Komandan, staf, atau pimpinan di atasnya.',
                                '4. Apa kebijakan memakai AI di perangkat dinas? Diam adalah kebijakan terburuk.',
                                '5. Apa yang tidak dimulai minggu pertama? Deploy agen hidup, unggah arsip personel, kontrak tanpa klausul data.',
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
                        'description' => 'Agenda 45 menit yang cukup untuk komandan dan staf.',
                        'content_type' => 'text',
                        'duration' => 10,
                        'rich_content' => $this->doc([
                            $this->heading('Mulai dari rapat, bukan dari tools', 1),
                            $this->paragraph('Undangan rapat yang hanya berisi demo vendor akan menghasilkan pembelian. Undangan yang berisi lima pertanyaan di lembar keputusan akan menghasilkan sikap.'),
                            $this->heading('Agenda singkat'),
                            $this->bullets([
                                'Sepuluh menit: satu prioritas tahun ini (bukan sepuluh).',
                                'Lima belas menit: data yang dilarang keluar.',
                                'Sepuluh menit: kebijakan perangkat dinas — boleh, wajib deklarasi, atau dilarang.',
                                'Sepuluh menit: siapa pemilik keputusan lanjutan.',
                            ]),
                            $this->paragraph('Kalau rapat berakhir tanpa pemilik, kesatuan belum adopsi. Kesatuan baru menonton slide.'),
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
                                'Menjalankan agen hidup di jaringan dinas “supaya kelihatan modern”.',
                                'Mengunggah arsip personel atau surat dinas ke vendor untuk “melatih AI kesatuan”.',
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
            'slug' => 'kuis-wajib-ai-untuk-militer',
            'description' => 'Kuis wajib untuk komandan, staf, dan personel. Bukan ujian operasi runtime.',
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

        $this->createMultipleChoice($assessment, 'Siapa audiens utama kursus AI untuk Militer?', [
            ['option_text' => 'Komandan, perwira staf, personel, dan pimpinan kesatuan yang memutuskan adopsi', 'is_correct' => true],
            ['option_text' => 'Teknisi yang men-deploy agen di produksi', 'is_correct' => false],
            ['option_text' => 'Prajurit yang ingin jadi operator OpenClaw', 'is_correct' => false],
            ['option_text' => 'Vendor yang menjual chatbot kesatuan', 'is_correct' => false],
        ], 1);

        $this->createTrueFalse(
            $assessment,
            'Membeli chatbot berarti kesatuan sudah mengadopsi AI.',
            false,
            2,
        );

        $this->createMultipleChoice($assessment, 'Apa yang tidak dimulai di minggu pertama adopsi?', [
            ['option_text' => 'Menjalankan agen hidup atau mengunggah arsip personel ke vendor tanpa kebijakan', 'is_correct' => true],
            ['option_text' => 'Rapat pimpinan dengan pertanyaan tentang data dan wewenang', 'is_correct' => false],
            ['option_text' => 'Membaca Open Course ini', 'is_correct' => false],
            ['option_text' => 'Menyusun kebijakan memakai AI di perangkat dinas', 'is_correct' => false],
        ], 3);

        $this->createManualShortAnswer(
            $assessment,
            'Sebutkan satu risiko yang harus disebut komandan sebelum menandatangani vendor AI.',
            4,
        );
    }
}
