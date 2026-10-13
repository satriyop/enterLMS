<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Tag;
use App\Models\User;
use App\Services\SeederThumbnailGenerator;
use Database\Seeders\Concerns\BuildsAcademyLessonContent;
use Database\Seeders\Concerns\BuildsAiEngineerSyllabus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Open catalog Course: three-day AI Engineer occupation training.
 *
 *   php artisan db:seed --class=AiEngineerCourseSeeder
 */
class AiEngineerCourseSeeder extends Seeder
{
    use BuildsAcademyLessonContent;
    use BuildsAiEngineerSyllabus;

    public const COURSE_TITLE = 'Insinyur Kecerdasan Artificial (AI Engineer)';

    public const COURSE_CODE = 'TIK.DSC0704';

    public const QUIZ_TITLE = 'Kuis Wajib Insinyur Kecerdasan Artificial';

    public const TUGAS_HARI_1 = 'Tugas Hari 1: Sasaran Teknis dan Rencana Proyek';

    public const TUGAS_HARI_2 = 'Tugas Hari 2: Dua Berita Acara Review';

    public const TUGAS_HARI_3 = 'Tugas Hari 3: Laporan Akhir';

    public const PREVIEW_LESSON_TITLE = 'Untuk siapa kursus ini';

    /**
     * @var list<string>
     */
    public const SECTION_TITLES = [
        'Hari 1. Sasaran teknis dan rencana proyek',
        'Hari 2. Meninjau model dan proyek',
        'Hari 3. Memasang, merawat, dan menutup',
    ];

    /**
     * @var list<string>
     */
    public const LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'Kasus berjalan: pengaduan Kota Arunika',
        'Posisi insinyur AI di antara ilmuwan data dan insinyur data',
        'Menentukan sasaran teknis solusi AI',
        'Kendala, lingkungan, dan kriteria belum siap',
        'Membuat rencana proyek solusi AI',
        'Jadwal, dependensi, dan risiko',
        'Artefak hari 1: sasaran dan rencana',
        'Paket model yang kamu terima',
        'Review pemodelan: data, fitur, dan metrik',
        'Bias, keterbatasan, dan keputusan layak pasang',
        'Review proyek data science',
        'Menyelaraskan artefak ke sasaran hari 1',
        'Berita acara: layak, tunda, atau tolak',
        'Artefak hari 2: dua berita acara',
        'Memasang solusi AI ke layanan operasional',
        'Health check, mode bayangan, dan rollback',
        'Merawat solusi AI',
        'Drift pengaduan dan pemicu latih ulang',
        'Program kerja pelindungan data pribadi',
        'Retensi, hak subjek, dan insiden data',
        'Laporan akhir proyek data science',
        'Menutup tiga hari: bukti, rubrik, dan batas sertifikat',
    ];

    /**
     * @var list<string>
     */
    public const DIAGRAM_LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'Kasus berjalan: pengaduan Kota Arunika',
        'Menentukan sasaran teknis solusi AI',
        'Membuat rencana proyek solusi AI',
        'Review pemodelan: data, fitur, dan metrik',
        'Review proyek data science',
        'Memasang solusi AI ke layanan operasional',
        'Merawat solusi AI',
        'Program kerja pelindungan data pribadi',
        'Laporan akhir proyek data science',
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
        $this->seedAssessments($course, $lmsAdmin);

        $this->command?->info('Open course ready: '.self::COURSE_TITLE);
    }

    private function ensureCategory(): Category
    {
        return Category::query()->firstOrCreate(
            ['slug' => 'insinyur-kecerdasan-artificial'],
            [
                'name' => 'Insinyur Kecerdasan Artificial',
                'description' => 'Pelatihan okupasi untuk meninjau, memasang, dan merawat solusi AI pada kasus nyata.',
                'order' => 7,
            ]
        );
    }

    private function seedCourse(User $admin, Category $category): Course
    {
        $thumbnailPath = app(SeederThumbnailGenerator::class)->generate(
            self::COURSE_TITLE,
            'courses/thumbnails',
            'insinyur-kecerdasan-artificial.jpg'
        );

        $course = Course::query()->updateOrCreate(
            ['title' => self::COURSE_TITLE],
            [
                'user_id' => $admin->id,
                'code' => self::COURSE_CODE,
                'slug' => Str::slug(self::COURSE_TITLE),
                'short_description' => 'Tiga hari untuk okupasi AI Engineer: sasaran, rencana, review, pasang, rawat, data pribadi, dan laporan akhir pada pengaduan layanan publik.',
                'long_description' => 'Open Course gratis dalam Bahasa Indonesia. Pelatihan tiga hari untuk okupasi Insinyur Kecerdasan Artificial (TIK.DSC0704, skema SKEMA-45). Kasus berjalannya Dinas Pelayanan Publik Kota Arunika: model klasifikasi pengaduan sudah ada, dan kamu yang meninjau lalu memutuskan ia layak masuk antrian warga atau belum. Delapan unit kompetensi dikerjakan sebagai artefak. Lesson memuat teks dan berkas Python yang kamu jalankan di mesinmu, tanpa pustaka tambahan. LMS ini tidak menjalankan kode itu. Menyelesaikannya tidak menerbitkan sertifikat BNSP dan tidak membuka Course terbatas.',
                'objectives' => [
                    'Menentukan sasaran teknis solusi AI untuk routing pengaduan, lengkap dengan ambang kelas mahal dan syarat belum siap',
                    'Menyusun rencana proyek yang memisahkan lingkup, ketergantungan, jadwal, dan risiko',
                    'Meninjau pemodelan dan proyek data science terhadap sasaran, lalu menulis berita acara tunda, layak, atau tolak',
                    'Merancang pemasangan, mode bayangan, rollback, dan perawatan tanpa mengirim teks warga ke luar jaringan dinas',
                    'Menyusun program kerja pelindungan data pribadi untuk alur routing, termasuk retensi dan insiden',
                    'Menutup proyek dengan laporan akhir yang mengikat putusan, sisa risiko, dan jendela sembilan puluh hari',
                ],
                'prerequisites' => [
                    'Kamu sudah menulis Python dan pernah melatih atau memakai model klasifikasi. Course ini tidak mengulang cara melatih model dari nol.',
                ],
                'category_id' => $category->id,
                'thumbnail_path' => $thumbnailPath,
                'status' => 'published',
                'visibility' => 'public',
                'difficulty_level' => 'advanced',
                'estimated_duration_minutes' => 1260,
                'is_paid' => false,
                'price' => null,
                'published_at' => now(),
                'published_by' => $admin->id,
            ]
        );

        $tag = Tag::query()->firstOrCreate(
            ['slug' => 'okupasi-ai-engineer'],
            ['name' => 'Okupasi AI Engineer']
        );
        $course->tags()->syncWithoutDetaching([$tag->id]);

        if ($this->catalogMatches($course, self::LESSON_TITLES) && $this->copyIsCurrent($course)) {
            $this->command?->info('Open course catalog already current: '.self::COURSE_TITLE);

            return $course->load('sections.lessons');
        }

        $this->replaceCourseLessons($course, $this->sections());

        return $course->load('sections.lessons');
    }

    private function copyIsCurrent(Course $course): bool
    {
        $intro = $course->lessons()->where('lessons.title', self::PREVIEW_LESSON_TITLE)->first();
        $closing = $course->lessons()->where('lessons.title', 'Menutup tiga hari: bukti, rubrik, dan batas sertifikat')->first();

        if (! $intro instanceof Lesson || ! $closing instanceof Lesson) {
            return false;
        }

        $json = json_encode($intro->rich_content ?? []);
        $closingJson = json_encode($closing->rich_content ?? []);

        return is_string($json)
            && str_contains($json, 'TIK.DSC0704')
            && str_contains($json, 'Kota Arunika')
            && str_contains($json, 'J.62PDP00.011.1')
            && str_contains($json, 'lembar September')
            && str_contains($json, 'atur_meja.py')
            && is_string($closingJson)
            && str_contains($closingJson, 'Rp2.500.000')
            && str_contains($closingJson, 'paket siang');
    }

    private function seedAssessments(Course $course, User $author): void
    {
        $this->seedQuiz($course, $author);
        $this->seedTugasHari1($course, $author);
        $this->seedTugasHari2($course, $author);
        $this->seedTugasHari3($course, $author);
    }

    private function seedQuiz(Course $course, User $author): void
    {
        $existing = $course->assessments()->where('title', self::QUIZ_TITLE)->with('questions')->first();

        if ($existing instanceof Assessment && $this->quizIsCurrent($existing)) {
            return;
        }

        if ($existing instanceof Assessment) {
            $this->forgetAssessment($existing);
        }

        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'user_id' => $author->id,
            'title' => self::QUIZ_TITLE,
            'slug' => 'kuis-wajib-insinyur-kecerdasan-artificial',
            'description' => 'Kuis wajib tentang keputusan insinyur AI pada pengaduan Kota Arunika. Bukan ujian memasang layanan.',
            'instructions' => "Jawab dari Lesson. Pilihan ganda dan benar/salah dinilai otomatis.\n\nIni mengukur sasaran, review, pemasangan, perawatan, dan pelindungan data pribadi. Angka pada soal kuis adalah latihan aturan. Paket siang pada tugas memakai angka lain. Lulus kuis tidak menerbitkan sertifikat okupasi.",
            'status' => 'published',
            'passing_score' => 70,
            'max_attempts' => 3,
            'is_required' => true,
            'time_limit_minutes' => 40,
            'allow_review' => true,
            'show_correct_answers' => true,
            'published_at' => now(),
            'published_by' => $author->id,
        ]);

        $this->createMultipleChoice($assessment, 'Langkah pertama yang selaras untuk insinyur AI pada pengaduan Kota Arunika adalah…', [
            ['option_text' => 'Menulis sasaran teknis routing, termasuk ambang kelas mahal dan lingkungan jaringan dinas', 'is_correct' => true],
            ['option_text' => 'Memilih encoder terbesar lalu mencari data yang cocok', 'is_correct' => false],
            ['option_text' => 'Memasang model ke antrian warga pada hari yang sama', 'is_correct' => false],
            ['option_text' => 'Membuka konsol runtime di dalam Lesson', 'is_correct' => false],
        ], 1);

        $this->createTrueFalse(
            $assessment,
            'Akurasi global cukup sebagai satu-satunya sasaran teknis klasifikasi lima meja pengaduan yang kelasnya timpang.',
            false,
            2,
        );

        $this->createMultipleChoice($assessment, 'Kelas yang paling mahal jika terlewat pada kasus Arunika adalah…', [
            ['option_text' => 'Infrastruktur, karena pengaduan seperti jalan ambles menunda bahaya fisik', 'is_correct' => true],
            ['option_text' => 'Kelas mana pun yang paling sering muncul, karena itu menaikkan akurasi', 'is_correct' => false],
            ['option_text' => 'Kelas yang encoder-nya paling baru', 'is_correct' => false],
            ['option_text' => 'Kelas yang tidak perlu recall', 'is_correct' => false],
        ], 3);

        $this->createTrueFalse(
            $assessment,
            'NIK dan nomor HP boleh disimpan di log prediksi agar audit routing lebih mudah.',
            false,
            4,
        );

        $this->createMultipleChoice($assessment, 'Mode bayangan sebelum produksi berarti…', [
            ['option_text' => 'Layanan memprediksi berdampingan dengan arahan Dina, tanpa mengubah antrian warga', 'is_correct' => true],
            ['option_text' => 'Model langsung menggantikan meja depan', 'is_correct' => false],
            ['option_text' => 'Lesson menjadi konsol untuk men-deploy agen', 'is_correct' => false],
            ['option_text' => 'Data uji dicampur ke data latih agar macro-F1 naik', 'is_correct' => false],
        ], 5);

        $this->createMultipleChoice($assessment, 'Latihan aturan, bukan paket siang: sebuah kandidat unggul 0,04 pada macro-F1 tetapi recall kelas mahal-nya 0,74, di bawah ambang 0,85 yang sudah ditandatangani. Putusan yang selaras pada latihan ini adalah…', [
            ['option_text' => 'Tolak kandidat itu untuk dinaikkan. Kenaikan rata-rata tidak menebus kelas mahal', 'is_correct' => true],
            ['option_text' => 'Pasang encoder karena macro-F1 adalah satu-satunya metrik yang penting', 'is_correct' => false],
            ['option_text' => 'Pasang keduanya dan biarkan warga memilih', 'is_correct' => false],
            ['option_text' => 'Abaikan ambang hari 1 karena angka uji sudah ada', 'is_correct' => false],
        ], 6);

        $this->createMultipleChoice($assessment, 'Contoh kebocoran pada review pemodelan pengaduan adalah…', [
            ['option_text' => 'Kalimat resolusi “sudah diteruskan ke bidang PU” ada di data latih, padahal belum ada saat pengaduan masuk', 'is_correct' => true],
            ['option_text' => 'Menyimpan panduan lima meja untuk anotator', 'is_correct' => false],
            ['option_text' => 'Mengukur recall infrastruktur secara terpisah', 'is_correct' => false],
            ['option_text' => 'Menulis syarat belum siap di sasaran teknis', 'is_correct' => false],
        ], 7);

        $this->createTrueFalse(
            $assessment,
            'Menyelesaikan Open Course ini menerbitkan sertifikat BNSP untuk okupasi Insinyur Kecerdasan Artificial.',
            false,
            8,
        );

        $this->createMultipleChoice($assessment, 'Dasar pemrosesan yang paling pas untuk routing pengaduan dinas adalah…', [
            ['option_text' => 'Pelaksanaan tugas pelayanan publik, dengan tujuan dibatasi pada routing', 'is_correct' => true],
            ['option_text' => 'Persetujuan yang disembunyikan di kaki halaman, lalu teks dikirim ke model publik', 'is_correct' => false],
            ['option_text' => 'Kepentingan komersial penyedia model', 'is_correct' => false],
            ['option_text' => 'Tidak perlu dasar karena datanya sudah ada di saluran pengaduan', 'is_correct' => false],
        ], 9);

        $this->createMultipleChoice($assessment, 'Pemicu latih ulang yang selaras dengan runbook Arunika adalah…', [
            ['option_text' => 'Macro-F1 mingguan turun melewati ambang, atau recall infrastruktur jatuh di bawah 0,80', 'is_correct' => true],
            ['option_text' => 'Setiap kali ada rilis model umum di berita', 'is_correct' => false],
            ['option_text' => 'Setiap pengaduan baru, tanpa review', 'is_correct' => false],
            ['option_text' => 'Saat kuis Course ini lulus', 'is_correct' => false],
        ], 10);
    }

    private function seedTugasHari1(Course $course, User $author): void
    {
        $existing = $course->assessments()->where('title', self::TUGAS_HARI_1)->with('questions')->first();

        if ($existing instanceof Assessment && $this->tugasContains($existing, 'J.62AIN00.002.1') && $this->tugasContains($existing, '8.000') && $this->tugasContains($existing, 'lembar_september.py')) {
            return;
        }

        if ($existing instanceof Assessment) {
            $this->forgetAssessment($existing);
        }

        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'user_id' => $author->id,
            'title' => self::TUGAS_HARI_1,
            'slug' => 'tugas-hari-1-sasaran-dan-rencana-ai-engineer',
            'description' => 'Dua artefak hari 1 pada pengaduan Kota Arunika: sasaran teknis dan rencana proyek.',
            'instructions' => 'Tulis dalam Bahasa Indonesia. Gunakan kasus Dinas Pelayanan Publik Kota Arunika. Jangan mengganti menjadi chatbot yang membalas warga. Tugas ini dinilai manusia.',
            'status' => 'published',
            'passing_score' => 70,
            'max_attempts' => 2,
            'is_required' => true,
            'time_limit_minutes' => null,
            'allow_review' => true,
            'show_correct_answers' => false,
            'published_at' => now(),
            'published_by' => $author->id,
        ]);

        $this->createEssay(
            $assessment,
            <<<'TEXT'
Tulis dua dokumen terpisah untuk routing pengaduan Kota Arunika. Pakai lembar September: 8.000 pengaduan, infrastruktur 800, sosial 480, 400 pengaduan per hari kerja.

Dokumen pertama, sasaran teknis unit J.62AIN00.002.1. Tulis keputusan, masukan pada saat pengaduan masuk, keluaran, dan lingkungan. Tunjukkan hitungan recall infrastruktur: 120 terlewat dari 800 menghasilkan 0,85. Tunjukkan hitungan ragu: 20 persen dari 400 adalah 80 pengaduan, kali 2 menit adalah 160 menit. Untuk sosial, pilih sendiri berapa yang boleh terlewat dari 480, hitung recall-nya, dan tulis apa yang terjadi jika teks bansos dibaca meja lain. Tutup dengan syarat berhenti dan larangan NIK di log.

Dokumen kedua, rencana proyek unit J.62AIN00.004.1. Halaman vendor yang harus kamu ganti berbunyi: “Akurasi 95 persen. Chatbot menjawab warga di WhatsApp. Go-live Jumat. GPU cloud. Tim data menyiapkan semuanya.” Tulis lingkup dan di luar lingkup, lima langkah dengan nama orang pada tiap ketergantungan, jadwal enam minggu yang berisi sepuluh hari kerja mode bayangan, dan empat risiko. Tiap risiko punya tanda yang bisa diperiksa, mitigasi, dan pemilik.

Tempel keluaran lembar_september.py untuk recall(120, 800) dan menit_ragu. Tempel satu baris log dari redaksi.py yang sudah dipotong, berisi [NIK], bukan 16 digit. Angka di evaluasi_latihan.py bukan angka tugas ini.
TEXT,
            'Cukup jika infrastruktur menunjukkan 800, 120, dan 0,85, termasuk keluaran recall dari lembar_september.py, ragu menunjukkan 80 pengaduan dan 160 menit, dan sosial menunjukkan 480 plus recall hasil bagi milik peserta. Ada satu baris log yang sudah dipotong, berisi [NIK] dan bukan 16 digit. Syarat berhenti menyebut data yang lebih baru dan ambang 0,85. Rencana menolak chatbot, GPU cloud, dan go-live Jumat, memakai nama orang, dan menjadwalkan mode bayangan sepuluh hari kerja. Belum cukup jika hanya menyalin tabel ambang tanpa pembagian, jika baris sosial menyalin 0,85 tanpa dibagi 480, jika log masih memuat 16 digit, atau jika solusinya membalas warga.',
            1,
            100,
        );
    }

    private function seedTugasHari2(Course $course, User $author): void
    {
        $existing = $course->assessments()->where('title', self::TUGAS_HARI_2)->with('questions')->first();

        if ($existing instanceof Assessment && $this->tugasContains($existing, 'paket siang') && $this->tugasContains($existing, '0,87')) {
            return;
        }

        if ($existing instanceof Assessment) {
            $this->forgetAssessment($existing);
        }

        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'user_id' => $author->id,
            'title' => self::TUGAS_HARI_2,
            'slug' => 'tugas-hari-2-berita-acara-review-ai-engineer',
            'description' => 'Dua berita acara: review pemodelan dan review proyek, pada paket siang.',
            'instructions' => 'Pakai hanya paket siang di pertanyaan. Jangan memakai angka latihan pada kuis. Tandai angka yang belum sah. Tugas ini dinilai manusia.',
            'status' => 'published',
            'passing_score' => 70,
            'max_attempts' => 2,
            'is_required' => true,
            'time_limit_minutes' => null,
            'allow_review' => true,
            'show_correct_answers' => false,
            'published_at' => now(),
            'published_by' => $author->id,
        ]);

        $this->createEssay(
            $assessment,
            <<<'TEXT'
Tulis dua berita acara terpisah pada paket siang Dinas Pelayanan Publik Kota Arunika. Pertama review pemodelan, unit J.62DMI00.015.1. Kedua review proyek, unit J.62DMI00.020.1. Masing-masing dibuka dengan putusan layak, tunda, atau tolak, lalu bukti. Ambang hari 1 yang berlaku: recall infrastruktur minimal 0,85, porsi ragu maksimal 20 persen, latensi p95 maksimal 300 ms di layanan dinas, nol NIK di log, rollback di bawah 15 menit.

Paket siang, dua kandidat.

Baseline-2, aturan kata kunci plus regresi logistik: macro-F1 0,66; recall infrastruktur 0,90; porsi ragu 29 persen. Satu dari delapan baris latih memuat kalimat “sudah diteruskan ke bidang PU”. Latensi tidak diukur.

Encoder-ringkas: macro-F1 0,79; recall infrastruktur 0,87; porsi ragu 14 persen. Splitnya acak dari minggu yang sama. Sembilan persen label berisi “ke Dina” atau nama petugas, bukan nama meja. p95 pada notebook 2,4 detik, belum diukur sebagai layanan.

Proyek: tidak ada piagam pemilik metrik. Go-live dijanjikan lima hari lagi. Serahan berupa notebook. Bendera rollback belum ada. Rencana memakai GPU cloud. Dinas tidak punya GPU itu dan tidak boleh mengekspor teks.
TEXT,
            'Cukup jika Encoder-ringkas tidak diluluskan: recall 0,87 belum sah karena split acak dan label nama petugas. Menyebut “ditolak karena recall 0,74” berarti menyalin kuis dan belum cukup. Baseline-2 tidak diluluskan: recall 0,90 belum sah karena kalimat resolusi, dan porsi ragu 29 persen melewati batas. Putusan produksi adalah tunda. Review proyek menunjuk notebook, tidak ada rollback, go-live lima hari, dan GPU cloud. Dua berita acara tidak boleh alasannya disalin sama. Mode bayangan hanya disebut bersama syarat: label beres, kebocoran dibuang, split berdasarkan waktu, latensi di layanan dinas, dan latihan rollback. Belum cukup jika putusannya layak pasang, atau kedua berita acara ditutup dengan “cukup menjanjikan”.',
            1,
            100,
        );
    }

    private function seedTugasHari3(Course $course, User $author): void
    {
        $existing = $course->assessments()->where('title', self::TUGAS_HARI_3)->with('questions')->first();

        if ($existing instanceof Assessment && $this->tugasContains($existing, 'J.62PDP00.011.1') && $this->tugasContains($existing, 'ktp saya hilang') && $this->tugasContains($existing, 'layanan_bayangan.py')) {
            return;
        }

        if ($existing instanceof Assessment) {
            $this->forgetAssessment($existing);
        }

        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'user_id' => $author->id,
            'title' => self::TUGAS_HARI_3,
            'slug' => 'tugas-hari-3-laporan-akhir-ai-engineer',
            'description' => 'Laporan akhir empat bagian: pasang, rawat, data pribadi, dan sembilan puluh hari.',
            'instructions' => 'Paragraf pertama adalah putusan dari berita acara paket siang, bukan arsitektur model. Empat judul di pertanyaan wajib ada. Tugas ini dinilai manusia.',
            'status' => 'published',
            'passing_score' => 70,
            'max_attempts' => 2,
            'is_required' => true,
            'time_limit_minutes' => null,
            'allow_review' => true,
            'show_correct_answers' => false,
            'published_at' => now(),
            'published_by' => $author->id,
        ]);

        $this->createEssay(
            $assessment,
            <<<'TEXT'
Tulis laporan akhir proyek routing pengaduan Kota Arunika. Pakai putusanmu pada paket siang. Jangan mengganti putusan agar laporan lebih halus. Wajib empat judul berikut.

Pemasangan, unit J.62AIN00.015.1. Gerbang, mode bayangan, dan rollback, hanya sepanjang berita acara mengizinkan. Tempel satu baris log dari layanan_bayangan.py. Baris itu memuat [NIK] atau [HP], bukan nomor aslinya, dan meja_antrian pada mode bayangan kosong.

Perawatan, unit J.62AIN00.017.1. Irama pantau dan pemicu latih ulang. Angka pemicu diturunkan dari ambang hari 1.

Data pribadi, unit J.62PDP00.011.1. Terapkan piagam di lesson. Isi RoPA lima baris: aktivitas, data, tujuan, dasar, penerima, retensi. Lalu terapkan pada dua teks: “ktp saya hilang, mohon diterbitkan ulang” dan “bantuan sosial ibu saya tidak cair”. Untuk tiap teks, tulis apa yang dipotong sebelum prediksi, apa yang tetap di arsip dinas, dan apa yang tidak boleh keluar dari dinas.

Sembilan puluh hari, unit J.62DMI00.021.1. Siapa melakukan apa, sisa risiko beserta pemiliknya, dan pemicu yang menghentikan pekerjaan.
TEXT,
            'Cukup jika pembuka sama dengan putusan tunda pada paket siang, dan Encoder-ringkas tidak disebut gagal kelas mahal. Pemasangan menjadwalkan mode bayangan bersyarat, bukan go-live lima hari, dan menyebut rollback. Perawatan punya pemicu berangka yang diturunkan dari ambang recall infrastruktur 0,85. RoPA lima baris lengkap. Pada KTP hilang, NIK dipotong sebelum prediksi, arsip dinas tetap menyimpan pengaduan sesuai retensi dinas, dan NIK tidak masuk log. Pada bantuan sosial, isi keadaan tidak keluar dari dinas dan meja tetap sosial. Sembilan puluh hari punya nama pemilik. Belum cukup jika laporan meminta sertifikat dari Course ini, mengirim teks mentah ke model publik, atau menyalin putusan kuis 0,74.',
            1,
            100,
        );
    }

    private function quizIsCurrent(Assessment $quiz): bool
    {
        $text = $quiz->questions->pluck('question_text')->implode(' ');

        return $quiz->questions->count() === 10
            && str_contains($text, 'kelas mahal')
            && str_contains($text, '0,74')
            && ! str_contains($text, 'Encoder Arunika');
    }

    private function tugasContains(Assessment $assessment, string $needle): bool
    {
        $text = $assessment->questions->pluck('question_text')->implode(' ');

        return str_contains($text, $needle);
    }

    private function forgetAssessment(Assessment $existing): void
    {
        $questions = Question::withTrashed()->where('assessment_id', $existing->id)->get();

        foreach ($questions as $question) {
            QuestionOption::withTrashed()->where('question_id', $question->id)->forceDelete();
            $question->forceDelete();
        }

        $existing->forceDelete();
    }
}
