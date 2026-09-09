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
        'UU 27/2022 PDP untuk kampus',
        'UNESCO untuk dosen: AI Competency Framework for Teachers',
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
                'short_description' => 'Kerangka mendalam adopsi AI kampus dengan Unissula sebagai kasus berjalan: peta jalan, tiga meja, data/privasi, kapasitas sivitas, dan deliverable rapat — tanpa menjalankan agen hidup.',
                'long_description' => 'Open Course gratis dalam Bahasa Indonesia. Ini satu kerangka komprehensif, bukan briefing 90 menit. Kasus berjalannya Universitas Islam Sultan Agung (Unissula, Semarang): PTS di bawah Yayasan Badan Wakaf Sultan Agung, akreditasi institusi Unggul BAN-PT, motto Bismillah Membangun Generasi Khaira Ummah, dan strategi Budaya Akademik Islami (BudAI). Kamu akan membedakan adopsi dari belanja tools; menyusun peta jalan bertahap; memisahkan pengajaran, tata kelola, dan operasional; menautkan keputusan data ke UU 27/2022 tentang Pelindungan Data Pribadi dan integritas akademik ke Permendikbudristek 39/2021; serta memakai UNESCO AI Competency Framework dan Recommendation on the Ethics of AI sebagai rujukan, bukan slide vendor. Lesson berisi teks, tabel, diagram, dan PDF takeaway. Ini bukan konsol. Menyelesaikan kursus ini tidak membuka Course terbatas.',
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
                'estimated_duration_minutes' => 480,
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
            && is_string($roadmapJson)
            && str_contains($roadmapJson, '"label":"Fondasi"')
            && str_contains($roadmapJson, 'YBWSA');
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

        $this->createMultipleChoice($assessment, 'Siapa audiens utama kerangka adopsi AI untuk Kampus dengan kasus Unissula?', [
            ['option_text' => 'Dekan, dosen, pemangku kepentingan, dan pimpinan YBWSA / rektorat yang memutuskan adopsi', 'is_correct' => true],
            ['option_text' => 'Teknisi yang men-deploy agen di produksi Kampus Kaligawe', 'is_correct' => false],
            ['option_text' => 'Mahasiswa yang ingin jadi operator OpenClaw', 'is_correct' => false],
            ['option_text' => 'Vendor yang menjual chatbot kampus', 'is_correct' => false],
        ], 1);

        $this->createTrueFalse(
            $assessment,
            'Membeli chatbot berarti Unissula sudah mengadopsi AI.',
            false,
            2,
        );

        $this->createMultipleChoice($assessment, 'Urutan peta jalan empat tahap Unissula yang benar adalah…', [
            ['option_text' => 'Sikap dan wewenang, fondasi, pilot, lalu kelembagaan', 'is_correct' => true],
            ['option_text' => 'Beli tools, wajibkan seluruh dosen, baru tulis kebijakan', 'is_correct' => false],
            ['option_text' => 'Deploy agen hidup di minggu pertama, lalu rapat YBWSA', 'is_correct' => false],
            ['option_text' => 'Copy-paste kebijakan NUS, lalu unggah arsip tugas ke vendor', 'is_correct' => false],
        ], 3);

        $this->createMultipleChoice($assessment, 'Fakta publik Unissula yang relevan untuk merangka adopsi AI adalah…', [
            ['option_text' => 'PTS di bawah YBWSA, akreditasi institusi Unggul BAN-PT, motto Khaira Ummah, dan strategi BudAI', 'is_correct' => true],
            ['option_text' => 'Unissula adalah PTN dengan kementerian sebagai yayasan', 'is_correct' => false],
            ['option_text' => 'Unissula tidak memiliki Fakultas Teknologi Industri', 'is_correct' => false],
            ['option_text' => 'BudAI adalah nama chatbot resmi kampus', 'is_correct' => false],
        ], 4);

        $this->createMultipleChoice($assessment, 'Tiga meja yang tidak boleh dicampur dalam satu SK Unissula adalah…', [
            ['option_text' => 'Pengajaran, tata kelola, dan operasional', 'is_correct' => true],
            ['option_text' => 'Marketing, pengadaan, dan humas', 'is_correct' => false],
            ['option_text' => 'Chatbot, agen, dan GPU', 'is_correct' => false],
            ['option_text' => 'Katalog Open Course, Restricted Course, dan konsol', 'is_correct' => false],
        ], 5);

        $this->createMultipleChoice($assessment, 'Dasar hukum Indonesia yang paling langsung mengatur data mahasiswa Unissula ke vendor AI adalah…', [
            ['option_text' => 'UU 27/2022 tentang Pelindungan Data Pribadi', 'is_correct' => true],
            ['option_text' => 'UNESCO AI Competency Framework for Students', 'is_correct' => false],
            ['option_text' => 'AppliedHE ranking ASEAN', 'is_correct' => false],
            ['option_text' => 'SK intern yang belum ada di Lesson ini', 'is_correct' => false],
        ], 6);

        $this->createTrueFalse(
            $assessment,
            'Menjalankan agen hidup di jaringan Unissula adalah langkah wajib minggu pertama peta jalan.',
            false,
            7,
        );

        $this->createMultipleChoice($assessment, 'Siapa yang memegang wewenang menandatangani data mahasiswa Unissula ke pihak ketiga?', [
            ['option_text' => 'Pimpinan yang disebut di lembar keputusan — rektorat dan/atau YBWSA — bukan dosen sendirian di kelas', 'is_correct' => true],
            ['option_text' => 'Mahasiswa yang klik setuju di chatbot', 'is_correct' => false],
            ['option_text' => 'Vendor karena sudah punya kebijakan privasi sendiri', 'is_correct' => false],
            ['option_text' => 'Tutor di Lesson ini', 'is_correct' => false],
        ], 8);

        $this->createMultipleChoice($assessment, 'UNESCO AI Competency Framework for Teachers (2024) membagi kompetensi dosen ke lima dimensi, termasuk…', [
            ['option_text' => 'Human-centred mindset, ethics of AI, fondasi/aplikasi, pedagogi AI, dan pembelajaran profesional', 'is_correct' => true],
            ['option_text' => 'Prompt engineering, GPU, dan fine-tuning saja', 'is_correct' => false],
            ['option_text' => 'Hanya coding Python untuk seluruh dosen Unissula', 'is_correct' => false],
            ['option_text' => 'Hanya pemasaran kampus di media sosial', 'is_correct' => false],
        ], 9);

        $this->createManualShortAnswer(
            $assessment,
            'Sebutkan satu risiko Unissula yang harus disebut di rapat pimpinan sebelum tanda tangan vendor AI, dan siapa yang memegang wewenang atas risiko itu.',
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
            && str_contains($text, 'risiko');
    }
}
