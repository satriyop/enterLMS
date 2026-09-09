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
use Database\Seeders\Concerns\BuildsAiForCorporateSyllabus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Open catalog Course: corporate AI-adoption framework, NEX Energi Masa Depan as the running case.
 *
 *   php artisan db:seed --class=AiForCorporateCourseSeeder
 */
class AiForCorporateCourseSeeder extends Seeder
{
    use BuildsAcademyLessonContent;
    use BuildsAiForCorporateSyllabus;

    public const COURSE_TITLE = 'AI untuk Korporat';

    public const QUIZ_TITLE = 'Kuis Wajib AI untuk Korporat';

    public const PREVIEW_LESSON_TITLE = 'Untuk siapa kursus ini';

    /**
     * @var list<string>
     */
    public const SECTION_TITLES = [
        'Konteks NEX Energi Masa Depan dan mandat pimpinan',
        'Adopsi, bukan belanja tools',
        'Peta jalan bertahap',
        'Use case yang andal di kantor NEX',
        'Tiga meja: operasional, tata kelola, SDM',
        'Data, wewenang, dan hukum',
        'Contoh dan deliverable NEX',
        'Rapat, batas, dan pustaka',
    ];

    /**
     * @var list<string>
     */
    public const LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'NEX Energi Masa Depan sebagai kasus berjalan',
        'Bukan kursus men-deploy agen',
        'Empat kursi: direksi, proyek, kantor, pengawas',
        'Apa artinya adopsi AI di perusahaan',
        'Bukan beli chatbot lalu selesai',
        'Tren, proyek IT, dan kebijakan: tiga benda',
        'Mengapa NEX tidak copy-paste strategi Big Tech mentah-mentah',
        'Peta jalan empat tahap NEX',
        'Tahap 0–1: sikap, wewenang, inventaris',
        'Tahap 2: pilot kantor, bukan PLTS otomatis',
        'Tahap 3–4: perluasan produk dan kelembagaan',
        'Cara menemukan use case AI yang andal',
        'Use case nex-project: desk, pipeline, solar',
        'Use case situs publik: simulasi ROI dan kajian',
        'Use case yang tidak dimulai: data pelanggan institusi ke model publik',
        'Tiga meja: operasional, tata kelola, SDM',
        'Meja operasional: lapangan PLTS dan proses kantor',
        'Meja tata kelola: kontrak, vendor, reputasi institusi',
        'Meja SDM: karyawan dan shadow AI',
        'Data pelanggan institusi dan karyawan NEX',
        'UU 27/2022 PDP dan data proyek PLTS',
        'Transfer ke vendor, rekaman rapat, kontrak',
        'Agent AI runtime: batas academy',
        'Yang sudah dikerjakan di NEX: nex-project',
        'Lembar keputusan pimpinan NEX',
        'Klausul data vendor untuk proyek PLTS',
        'Peta jalan 24 bulan NEX',
        'Risiko yang pimpinan harus sebutkan',
        'Mulai dari rapat, bukan dari tools',
        'Apa yang tidak dimulai minggu pertama',
        'Pustaka: UNESCO, OECD, NIST, UU PDP, NEX',
    ];

    /**
     * Roadmap, framework, and NEX-deliverable Lessons that must render a figure.
     *
     * @var list<string>
     */
    public const DIAGRAM_LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'Empat kursi: direksi, proyek, kantor, pengawas',
        'Bukan beli chatbot lalu selesai',
        'Peta jalan empat tahap NEX',
        'Cara menemukan use case AI yang andal',
        'Use case nex-project: desk, pipeline, solar',
        'Tiga meja: operasional, tata kelola, SDM',
        'UU 27/2022 PDP dan data proyek PLTS',
        'Agent AI runtime: batas academy',
        'Peta jalan 24 bulan NEX',
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
            ['slug' => 'adopsi-ai-korporat'],
            [
                'name' => 'Adopsi AI Korporat',
                'description' => 'Kerangka mendalam untuk pimpinan yang memutuskan adopsi AI di perusahaan, dengan NEX Energi Masa Depan sebagai kasus berjalan.',
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
                'short_description' => 'Kerangka mendalam adopsi AI korporat dengan NEX Energi Masa Depan sebagai kasus berjalan: peta jalan, use case andal, tiga meja, data/hukum, dan deliverable rapat — tanpa menjalankan agen hidup.',
                'long_description' => 'Open Course gratis dalam Bahasa Indonesia. Ini satu kerangka komprehensif, bukan briefing 90 menit. Kasus berjalannya NEX Energi Masa Depan (https://energimasadepan.com): PLTS atap untuk operasional institusi, dengan nex-project (https://project.energimasadepan.com) sebagai produk kantor yang sudah menginfus AI. Kamu akan membedakan adopsi dari belanja tools; menyusun peta jalan empat tahap; menyaring use case kantor yang andal (desk, pipeline, simulasi ROI — bukan unggah data pelanggan institusi ke model publik); memisahkan operasional, tata kelola, dan SDM; menautkan data proyek dan pelanggan ke UU 27/2022 PDP; serta memakai UNESCO Recommendation on the Ethics of AI, OECD AI Principles, dan NIST AI RMF sebagai rujukan, bukan slide vendor. Lesson berisi teks, tabel, diagram, dan PDF takeaway. Ini bukan konsol. Menyelesaikan kursus ini tidak membuka Course terbatas.',
                'objectives' => [
                    'Menjelaskan adopsi AI perusahaan sebagai keputusan wewenang NEX, bukan pembelian chatbot',
                    'Menyusun peta jalan empat tahap: sikap, fondasi, pilot kantor, kelembagaan',
                    'Menyaring use case andal di nex-project, simulasi ROI, dan kajian — menolak unggah data pelanggan institusi ke model publik',
                    'Memisahkan tiga meja: operasional, tata kelola, dan SDM',
                    'Menautkan data proyek PLTS dan pelanggan institusi ke UU 27/2022 PDP',
                    'Memakai UNESCO, OECD, dan NIST AI RMF sebagai rujukan, bukan pengganti hukum Indonesia',
                    'Membawa deliverable rapat: lembar keputusan, klausul vendor, dan peta jalan 24 bulan, dengan contoh nex-project',
                ],
                'prerequisites' => [
                    'Tidak ada prasyarat teknis. Ditujukan untuk pimpinan yang memutuskan — direksi, manajer proyek, kantor, pengawas — bukan yang men-deploy.',
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
            ['slug' => 'korporat'],
            ['name' => 'Korporat']
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
        $intro = Lesson::query()
            ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
            ->where('course_sections.course_id', $course->id)
            ->where('lessons.title', self::PREVIEW_LESSON_TITLE)
            ->whereNull('lessons.deleted_at')
            ->first(['lessons.*']);

        $roadmap = Lesson::query()
            ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
            ->where('course_sections.course_id', $course->id)
            ->where('lessons.title', 'Peta jalan empat tahap NEX')
            ->whereNull('lessons.deleted_at')
            ->first(['lessons.*']);

        $json = json_encode($intro instanceof Lesson ? $intro->rich_content : []);
        $roadmapJson = json_encode($roadmap instanceof Lesson ? $roadmap->rich_content : []);

        return is_string($json)
            && str_contains($json, 'NEX')
            && str_contains($json, 'peta jalan')
            && str_contains($json, 'Energi Masa Depan')
            && is_string($roadmapJson)
            && str_contains($roadmapJson, '"label":"Fondasi"')
            && str_contains($roadmapJson, 'nex-project');
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
            'slug' => 'kuis-wajib-ai-untuk-korporat',
            'description' => 'Kuis wajib kerangka adopsi AI korporat dengan kasus NEX Energi Masa Depan. Mengukur peta jalan, nex-project, dan wewenang/risiko — bukan ujian operasi runtime.',
            'instructions' => "Jawab dari Lesson. Pilihan ganda dan benar/salah dinilai otomatis. Jawaban singkat menunggu LMS Admin.\n\nIni mengukur kerangka adopsi: peta jalan, NEX, wewenang dan risiko. Bukan ujian men-deploy agen.",
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

        $this->createMultipleChoice($assessment, 'Siapa audiens utama kerangka adopsi AI untuk Korporat dengan kasus NEX Energi Masa Depan?', [
            ['option_text' => 'Direksi, manajer proyek, kantor, dan pengawas yang memutuskan adopsi', 'is_correct' => true],
            ['option_text' => 'Teknisi yang men-deploy agen di produksi nex-project', 'is_correct' => false],
            ['option_text' => 'Staf yang ingin jadi operator OpenClaw', 'is_correct' => false],
            ['option_text' => 'Vendor yang menjual chatbot perusahaan', 'is_correct' => false],
        ], 1);

        $this->createTrueFalse(
            $assessment,
            'Membeli chatbot berarti NEX Energi Masa Depan sudah mengadopsi AI.',
            false,
            2,
        );

        $this->createMultipleChoice($assessment, 'Urutan peta jalan empat tahap NEX yang benar adalah…', [
            ['option_text' => 'Sikap dan wewenang, fondasi, pilot kantor, lalu kelembagaan', 'is_correct' => true],
            ['option_text' => 'Beli tools, wajibkan seluruh staf, baru tulis kebijakan', 'is_correct' => false],
            ['option_text' => 'Deploy agen hidup di minggu pertama, lalu rapat direksi', 'is_correct' => false],
            ['option_text' => 'Copy-paste strategi Big Tech, lalu unggah arsip pelanggan institusi ke vendor', 'is_correct' => false],
        ], 3);

        $this->createMultipleChoice($assessment, 'Manakah use case AI yang andal untuk dimulai di NEX?', [
            ['option_text' => 'Desk nex-project, pipeline, dan simulasi ROI — dengan manusia mengesahkan', 'is_correct' => true],
            ['option_text' => 'Mengunggah data pelanggan institusi ke model publik di minggu pertama', 'is_correct' => false],
            ['option_text' => 'Menyalin seluruh arsip proyek PLTS ke vendor agar AI-nya satu', 'is_correct' => false],
            ['option_text' => 'Chatbot yang menandatangani kontrak institusi tanpa direksi', 'is_correct' => false],
        ], 4);

        $this->createMultipleChoice($assessment, 'Tiga meja yang tidak boleh dicampur dalam satu keputusan NEX adalah…', [
            ['option_text' => 'Operasional, tata kelola, dan SDM', 'is_correct' => true],
            ['option_text' => 'Marketing, pengadaan, dan humas', 'is_correct' => false],
            ['option_text' => 'Chatbot, agen, dan GPU', 'is_correct' => false],
            ['option_text' => 'Katalog Open Course, Restricted Course, dan konsol', 'is_correct' => false],
        ], 5);

        $this->createMultipleChoice($assessment, 'Dasar hukum Indonesia yang paling langsung mengatur data pelanggan institusi NEX ke vendor AI adalah…', [
            ['option_text' => 'UU 27/2022 tentang Pelindungan Data Pribadi', 'is_correct' => true],
            ['option_text' => 'OECD AI Principles', 'is_correct' => false],
            ['option_text' => 'NIST AI RMF 1.0', 'is_correct' => false],
            ['option_text' => 'SK intern yang belum ada di Lesson ini', 'is_correct' => false],
        ], 6);

        $this->createTrueFalse(
            $assessment,
            'Menjalankan agen hidup di jaringan kantor NEX adalah langkah wajib minggu pertama peta jalan.',
            false,
            7,
        );

        $this->createMultipleChoice($assessment, 'Siapa yang memegang wewenang menandatangani data proyek PLTS NEX ke pihak ketiga?', [
            ['option_text' => 'Pimpinan yang disebut di lembar keputusan — direksi dan/atau pengawas — bukan operator nex-project sendirian', 'is_correct' => true],
            ['option_text' => 'Karyawan yang klik setuju di chatbot', 'is_correct' => false],
            ['option_text' => 'Vendor karena sudah punya kebijakan privasi sendiri', 'is_correct' => false],
            ['option_text' => 'Tutor di Lesson ini', 'is_correct' => false],
        ], 8);

        $this->createMultipleChoice($assessment, 'nex-project di kerangka ini adalah…', [
            ['option_text' => 'Produk kantor NEX di project.energimasadepan.com yang sudah menginfus AI di desk, pipeline, dan solar — contoh adopsi-di-produk, bukan konsol academy', 'is_correct' => true],
            ['option_text' => 'Restricted Course OpenClaw yang terbuka setelah lulus kuis', 'is_correct' => false],
            ['option_text' => 'Chatbot pelanggan institusi yang menandatangani kontrak', 'is_correct' => false],
            ['option_text' => 'Nama vendor asing yang wajib dibeli NEX', 'is_correct' => false],
        ], 9);

        $this->createManualShortAnswer(
            $assessment,
            'Sebutkan satu risiko NEX yang harus disebut di rapat pimpinan sebelum tanda tangan vendor AI, dan siapa yang memegang wewenang atas risiko itu.',
            10,
        );
    }

    private function quizIsCurrent(Assessment $quiz): bool
    {
        $text = $quiz->questions->pluck('question_text')->implode(' ');

        return $quiz->questions->count() >= 8
            && str_contains($text, 'peta jalan')
            && str_contains($text, 'NEX')
            && str_contains($text, 'wewenang')
            && str_contains($text, 'risiko');
    }
}
