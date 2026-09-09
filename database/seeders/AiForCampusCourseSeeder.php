<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Tag;
use App\Models\User;
use App\Services\SeederThumbnailGenerator;
use Database\Seeders\Concerns\BuildsAcademyLessonContent;
use Database\Seeders\Concerns\BuildsAiForCampusSyllabus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Open catalog Course: campus AI-adoption framework, Unissula as the running case.
 *
 *   php artisan db:seed --class=AiForCampusCourseSeeder
 */
class AiForCampusCourseSeeder extends Seeder
{
    use BuildsAcademyLessonContent;
    use BuildsAiForCampusSyllabus;

    public const COURSE_TITLE = 'AI untuk Kampus';

    public const QUIZ_TITLE = 'Kuis Wajib AI untuk Kampus';

    public const PREVIEW_LESSON_TITLE = 'Untuk siapa kursus ini';

    /**
     * @var list<string>
     */
    public const SECTION_TITLES = [
        'Konteks Unissula dan mandat pimpinan',
        'Adopsi, bukan belanja tools',
        'Peta jalan bertahap',
        'Tiga meja: pengajaran, tata kelola, operasional',
        'Data, privasi, dan hukum',
        'Kapasitas sivitas',
        'Contoh dan deliverable Unissula',
        'Rapat, batas, dan pustaka',
    ];

    /**
     * @var list<string>
     */
    public const LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'Unissula sebagai kasus berjalan',
        'Bukan kursus men-deploy agen',
        'Empat kursi, satu meja: YBWSA, rektorat, dekan, dosen',
        'Apa artinya adopsi AI di kampus',
        'Bukan beli chatbot lalu selesai',
        'Tren, proyek IT, dan kebijakan: tiga benda',
        'Mengapa Unissula tidak copy-paste kebijakan luar negeri',
        'Peta jalan empat tahap Unissula',
        'Tahap 0–1: sikap, wewenang, inventaris',
        'Tahap 2: pilot FTI dan satu fakultas non-teknik',
        'Tahap 3–4: perluasan dan kelembagaan',
        'Tiga meja: pengajaran, tata kelola, operasional',
        'Meja pengajaran: silabus, tugas, ujian, skripsi',
        'Meja tata kelola: SK, anggaran, vendor, reputasi',
        'Meja operasional: apa yang academy ini tidak kerjakan',
        'Data mahasiswa dan dosen Unissula',
        'UU 27/2022 PDP untuk kampus',
        'Transfer ke vendor, rekaman kuliah, SIA',
        'Integritas akademik: Permendikbudristek 39/2021',
        'UNESCO untuk dosen: AI Competency Framework for Teachers',
        'UNESCO untuk mahasiswa: AI Competency Framework for Students',
        'Dari FTI ke fakultas kesehatan dan hukum',
        'Lembar keputusan pimpinan Unissula',
        'Kebijakan kelas: contoh FTI Informatika',
        'Klausul data vendor untuk LMS dan SIA',
        'Peta jalan 24 bulan Unissula',
        'Risiko yang pimpinan harus sebutkan',
        'Mulai dari rapat, bukan dari tools',
        'Apa yang tidak dimulai minggu pertama',
        'Pustaka: UNESCO, UU PDP, Dikti, Unissula',
    ];

    /**
     * Roadmap, framework, and Unissula-deliverable Lessons that must render a figure.
     *
     * @var list<string>
     */
    public const DIAGRAM_LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'Empat kursi, satu meja: YBWSA, rektorat, dekan, dosen',
        'Bukan beli chatbot lalu selesai',
        'Peta jalan empat tahap Unissula',
        'Tiga meja: pengajaran, tata kelola, operasional',
        'Meja pengajaran: silabus, tugas, ujian, skripsi',
        'UU 27/2022 PDP untuk kampus',
        'Transfer ke vendor, rekaman kuliah, SIA',
        'UNESCO untuk dosen: AI Competency Framework for Teachers',
        'Dari FTI ke fakultas kesehatan dan hukum',
        'Peta jalan 24 bulan Unissula',
        'Risiko yang pimpinan harus sebutkan',
        'Mulai dari rapat, bukan dari tools',
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
                'description' => 'Kerangka mendalam untuk sivitas yang memutuskan adopsi AI di kampus, dengan Unissula sebagai kasus berjalan.',
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
                'short_description' => 'Kerangka mendalam adopsi AI kampus: satu rapat Unissula, tiga modul vendor, peta jalan, tiga meja, UU PDP, UNESCO, dan deliverable terisi — tanpa menjalankan agen hidup.',
                'long_description' => 'Open Course gratis dalam Bahasa Indonesia. Kasus berjalannya satu rapat di Universitas Islam Sultan Agung (Unissula, Semarang): vendor fiktif Gema Kampus menawarkan Chat PMB, Baca Tugas, dan Kenal Mahasiswa (SIA). Informatika FTI sudah mengajar kecerdasan buatan; 12 fakultas lain masih diam. Kamu memisahkan adopsi dari belanja; menyusun peta jalan; memisahkan pengajaran, tata kelola, dan operasional; menaut ke UU 27/2022 PDP, Permendikbudristek 39/2021, Panduan GenAI Kemendikbudristek 2024, dan UNESCO. Lesson berisi teks, tabel, diagram, dan PDF takeaway yang sudah terisi untuk rapat. Ini bukan konsol. Menyelesaikan kursus ini tidak membuka Course terbatas.',
                'objectives' => [
                    'Menjelaskan adopsi AI kampus sebagai keputusan wewenang Unissula, bukan pembelian chatbot',
                    'Menyusun peta jalan empat tahap: sikap, fondasi, pilot, kelembagaan',
                    'Memisahkan tiga meja: pengajaran, tata kelola, dan operasional',
                    'Menautkan data mahasiswa/dosen ke UU 27/2022 PDP dan karya ilmiah ke Permendikbudristek 39/2021',
                    'Menggunakan UNESCO AI competency (dosen dan mahasiswa) serta Recommendation on the Ethics of AI sebagai rujukan kapasitas',
                    'Membawa deliverable rapat: lembar keputusan, kebijakan kelas, klausul vendor, dan peta jalan 24 bulan',
                ],
                'prerequisites' => [
                    'Tidak ada prasyarat teknis. Ditujukan untuk sivitas yang memutuskan — dekan, dosen, pemangku kepentingan, ketua yayasan — bukan yang men-deploy.',
                ],
                'category_id' => $category->id,
                'thumbnail_path' => $thumbnailPath,
                'status' => 'published',
                'visibility' => 'public',
                'difficulty_level' => 'beginner',
                'estimated_duration_minutes' => 560,
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

        if ($this->catalogMatches($course, self::LESSON_TITLES) && $this->contentFingerprintIsCurrent($course)) {
            $this->command?->info('Open course catalog already current: '.self::COURSE_TITLE);

            return $course->load('sections.lessons');
        }

        $this->replaceCourseLessons($course, $this->sections());

        return $course->load('sections.lessons');
    }

    private function contentFingerprintIsCurrent(Course $course): bool
    {
        $intro = $course->lessons()->where('lessons.title', self::PREVIEW_LESSON_TITLE)->first();
        $json = json_encode($intro?->rich_content ?? []);

        $roadmap = $course->lessons()->where('lessons.title', 'Peta jalan empat tahap Unissula')->first();
        $roadmapJson = json_encode($roadmap?->rich_content ?? []);

        return is_string($json)
            && str_contains($json, 'Unissula')
            && str_contains($json, 'peta jalan')
            && str_contains($json, 'UNESCO')
            && str_contains($json, 'Gema Kampus')
            && is_string($roadmapJson)
            && str_contains($roadmapJson, '"label":"Fondasi"')
            && str_contains($roadmapJson, 'YBWSA')
            && str_contains($roadmapJson, 'Gema Kampus');
    }

    private function seedQuiz(Course $course, User $author): void
    {
        $existing = Assessment::query()
            ->where('course_id', $course->id)
            ->where('title', self::QUIZ_TITLE)
            ->with('questions')
            ->first();

        if ($existing && $this->quizIsCurrent($existing)) {
            return;
        }

        if ($existing) {
            $questions = Question::withTrashed()->where('assessment_id', $existing->id)->get();
            foreach ($questions as $question) {
                QuestionOption::withTrashed()->where('question_id', $question->id)->forceDelete();
                $question->forceDelete();
            }
            $existing->forceDelete();
        }

        $assessment = Assessment::query()->create([
            'course_id' => $course->id,
            'user_id' => $author->id,
            'title' => self::QUIZ_TITLE,
            'slug' => 'kuis-wajib-ai-untuk-kampus',
            'description' => 'Kuis wajib kerangka adopsi AI kampus dengan kasus Unissula. Mengukur peta jalan, konteks Unissula, dan wewenang/risiko — bukan ujian operasi runtime.',
            'instructions' => "Jawab dari Lesson. Pilihan ganda dan benar/salah dinilai otomatis. Jawaban singkat menunggu LMS Admin.\n\nIni mengukur kerangka adopsi: peta jalan, Unissula, wewenang dan risiko. Bukan ujian men-deploy agen.",
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

        $this->createMultipleChoice($assessment, 'Gema Kampus menawarkan tiga modul ke Unissula. Siapa yang memegang wewenang menandatangani Kenal Mahasiswa (sambungan SIA)?', [
            ['option_text' => 'YBWSA dan rektorat sebagai pengendali data — bukan Bu Laila atau admin LMS sendirian', 'is_correct' => true],
            ['option_text' => 'Dosen Informatika karena prodi sudah punya mata kuliah Kecerdasan Buatan', 'is_correct' => false],
            ['option_text' => 'Mahasiswa yang klik setuju di chatbot', 'is_correct' => false],
            ['option_text' => 'Vendor, karena kebijakan privasinya sudah lengkap', 'is_correct' => false],
        ], 1);

        $this->createTrueFalse(
            $assessment,
            'Membeli modul Chat PMB dari Gema Kampus berarti Unissula sudah mengadopsi AI.',
            false,
            2,
        );

        $this->createMultipleChoice($assessment, 'Jika Unissula menandatangani Baca Tugas di minggu pertama, tahap peta jalan mana yang dilompati?', [
            ['option_text' => 'Sikap, fondasi, dan klausul data — vendor masuk sebelum wewenang dan peta data selesai', 'is_correct' => true],
            ['option_text' => 'Hanya pelatihan Create UNESCO untuk seluruh dosen', 'is_correct' => false],
            ['option_text' => 'Tidak ada yang dilompati; tools dulu adalah peta jalan yang benar', 'is_correct' => false],
            ['option_text' => 'Tahap kelembagaan SPMI, karena audit harus di awal', 'is_correct' => false],
        ], 3);

        $this->createMultipleChoice($assessment, 'Visi publik Informatika FTI Unissula (Panduan Akademik 2024/2025) yang mengubah rumusan adopsi adalah…', [
            ['option_text' => 'Prodi sudah berfokus pada kecerdasan buatan; yang belum ada adalah kebijakan GenAI untuk 13 fakultas dan data SIA', 'is_correct' => true],
            ['option_text' => 'Unissula adalah PTN sehingga yayasan tidak duduk di rapat vendor', 'is_correct' => false],
            ['option_text' => 'FTI tidak mengajar AI, jadi kampus harus beli chatbot agar kelihatan modern', 'is_correct' => false],
            ['option_text' => 'BudAI adalah nama add-on LMS resmi', 'is_correct' => false],
        ], 4);

        $this->createMultipleChoice($assessment, 'Dimas mengumpulkan take-home kode. Tiga meja mana yang tersentuh sekaligus, dan mengapa tidak boleh satu SK Gema Kampus?', [
            ['option_text' => 'Pengajaran (apa yang diuji), tata kelola (apakah teks boleh ke vendor), operasional (siapa mematikan add-on)', 'is_correct' => true],
            ['option_text' => 'Hanya pengajaran, karena tugas adalah urusan dosen', 'is_correct' => false],
            ['option_text' => 'Hanya operasional, karena LMS adalah urusan TIK', 'is_correct' => false],
            ['option_text' => 'Marketing, PMB, dan humas', 'is_correct' => false],
        ], 5);

        $this->createMultipleChoice($assessment, 'Kenal Mahasiswa menarik NIM dan status dari SIA Unissula ke Gema Kampus. Dasar hukum yang paling langsung adalah…', [
            ['option_text' => 'UU 27/2022 tentang Pelindungan Data Pribadi — Unissula pengendali, vendor prosesor hanya jika tujuan tetap milik kampus', 'is_correct' => true],
            ['option_text' => 'UNESCO AI Competency Framework for Students', 'is_correct' => false],
            ['option_text' => 'AppliedHE ranking ASEAN', 'is_correct' => false],
            ['option_text' => 'Syarat layanan vendor di portal klik-setuju', 'is_correct' => false],
        ], 6);

        $this->createTrueFalse(
            $assessment,
            'Menjalankan agen hidup di jaringan Unissula adalah langkah wajib minggu pertama peta jalan.',
            false,
            7,
        );

        $this->createMultipleChoice($assessment, 'Pak Irfan (FH) di tingkat UNESCO Acquire minggu ini yang paling tepat adalah…', [
            ['option_text' => 'Mengganti esai take-home menjadi dossier sumber plus lisan singkat, dan menolak teks memo ke vendor tanpa dasar PDP', 'is_correct' => true],
            ['option_text' => 'Fine-tuning model di GPU FTI sampai tingkat Create', 'is_correct' => false],
            ['option_text' => 'Mewajibkan seluruh mahasiswa FH berlangganan merek Gema Kampus', 'is_correct' => false],
            ['option_text' => 'Menggantungkan skripsi pada skor detektor AI saja', 'is_correct' => false],
        ], 8);

        $this->createMultipleChoice($assessment, 'Mengapa FK Unissula tidak dipilih sebagai fakultas non-teknik pada pilot tahap 2 peta jalan?', [
            ['option_text' => 'Data praktikum klinis adalah data spesifik UU 27/2022; risiko PDP lebih berat daripada FH yang menguji karya tulis dulu', 'is_correct' => true],
            ['option_text' => 'FK tidak terakreditasi, jadi tidak penting', 'is_correct' => false],
            ['option_text' => 'Dosen FK sudah Create UNESCO, tidak perlu kebijakan', 'is_correct' => false],
            ['option_text' => 'Gema Kampus menolak fakultas kesehatan', 'is_correct' => false],
        ], 9);

        $this->createManualShortAnswer(
            $assessment,
            'Gema Kampus mendesak Unissula menandatangani Baca Tugas minggu ini. Sebutkan satu risiko yang harus disebut di rapat, dan siapa yang memegang wewenang menolak atau menerima modul itu.',
            10,
        );
    }

    private function quizIsCurrent(Assessment $quiz): bool
    {
        $text = $quiz->questions->pluck('question_text')->implode(' ');

        return $quiz->questions->count() >= 8
            && str_contains($text, 'Unissula')
            && str_contains($text, 'peta jalan')
            && str_contains($text, 'wewenang')
            && str_contains($text, 'risiko')
            && str_contains($text, 'Gema Kampus');
    }
}
