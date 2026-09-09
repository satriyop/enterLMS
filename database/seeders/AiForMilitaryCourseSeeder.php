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
use Database\Seeders\Concerns\BuildsAiForMilitarySyllabus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Open catalog Course: TNI AD AI-adoption framework, Korem 074/072 as staff cases.
 *
 *   php artisan db:seed --class=AiForMilitaryCourseSeeder
 */
class AiForMilitaryCourseSeeder extends Seeder
{
    use BuildsAcademyLessonContent;
    use BuildsAiForMilitarySyllabus;

    public const COURSE_TITLE = 'AI untuk Militer';

    public const QUIZ_TITLE = 'Kuis Wajib AI untuk Militer';

    public const PREVIEW_LESSON_TITLE = 'Untuk siapa kursus ini';

    public const YOUTUBE_AGENTS = 'https://www.youtube.com/watch?v=d0wUM8hIaxE';

    public const YOUTUBE_TMMD = 'https://www.youtube.com/watch?v=6Y_RbbVU78Q';

    public const YOUTUBE_ICRC = 'https://www.youtube.com/watch?v=KkhAVzSexBc';

    public const YOUTUBE_KOMINFO_ETIKA = 'https://www.youtube.com/watch?v=vfuSFTA43fU';

    /**
     * Text Lessons that carry a contextual YouTube clip above the briefing.
     *
     * @var list<string>
     */
    public const YOUTUBE_LESSON_TITLES = [
        'Bukan beli chatbot lalu selesai',
        'Use case rapat, surat, dan pelatihan',
        'Use case yang tidak dimulai: intel, senjata, targeting',
        'SPBE, BSSN, dan vendor di luar kesatuan',
    ];

    /**
     * @var list<string>
     */
    public const SECTION_TITLES = [
        'Konteks TNI AD dan mandat pimpinan',
        'Adopsi, bukan belanja tools',
        'Peta jalan bertahap',
        'Use case yang andal di kesatuan',
        'Tiga meja: tugas, komando, personel',
        'Data, wewenang, dan hukum',
        'Contoh dan deliverable Korem 074 dan 072',
        'Rapat, batas, dan pustaka',
    ];

    /**
     * @var list<string>
     */
    public const LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'TNI AD sebagai kasus berjalan',
        'Bukan kursus men-deploy agen',
        'Empat kursi: Danrem, Kasrem, staf, personel',
        'Apa artinya adopsi AI di kesatuan',
        'Bukan beli chatbot lalu selesai',
        'Tren, proyek IT, dan perintah: tiga benda',
        'Mengapa kesatuan tidak copy-paste strategi NATO mentah-mentah',
        'Peta jalan empat tahap TNI AD',
        'Tahap 0–1: sikap, wewenang, inventaris',
        'Tahap 2: pilot staf Korem, bukan senjata',
        'Tahap 3–4: perluasan Kodim dan kelembagaan',
        'Cara menemukan use case AI yang andal',
        'Use case administrasi personel: Sisfomil',
        'Use case rapat, surat, dan pelatihan',
        'Use case yang tidak dimulai: intel, senjata, targeting',
        'Tiga meja: tugas, komando, personel',
        'Meja tugas: staf dan administrasi',
        'Meja komando: anggaran, vendor, klasifikasi',
        'Meja personel: perangkat dinas dan shadow AI',
        'Data personel dan kesatuan',
        'UU 27/2022 PDP dan data prajurit',
        'SPBE, BSSN, dan vendor di luar kesatuan',
        'Agent AI runtime: batas academy',
        'Yang sudah dikerjakan di Korem 074: Sisfomil',
        'Yang sudah dikerjakan di Korem 072: Sipamungkas',
        'Lembar keputusan pimpinan kesatuan',
        'Peta jalan 24 bulan TNI AD',
        'Risiko yang pimpinan harus sebutkan',
        'Mulai dari rapat, bukan dari tools',
        'Apa yang tidak dimulai minggu pertama',
        'Pustaka: NATO, DoD, UU TNI, PDP, SPBE',
    ];

    /**
     * Roadmap, framework, and Korem-deliverable Lessons that must render a figure.
     *
     * @var list<string>
     */
    public const DIAGRAM_LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'Empat kursi: Danrem, Kasrem, staf, personel',
        'Bukan beli chatbot lalu selesai',
        'Peta jalan empat tahap TNI AD',
        'Cara menemukan use case AI yang andal',
        'Use case administrasi personel: Sisfomil',
        'Tiga meja: tugas, komando, personel',
        'Agent AI runtime: batas academy',
        'Peta jalan 24 bulan TNI AD',
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
            ['slug' => 'adopsi-ai-militer'],
            [
                'name' => 'Adopsi AI Militer',
                'description' => 'Kerangka mendalam untuk pimpinan kesatuan yang memutuskan adopsi AI di TNI AD, dengan Korem 074 dan 072 sebagai kasus staf.',
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
                'short_description' => 'Kerangka mendalam adopsi AI TNI AD dengan Korem 074/Warastratama (Sisfomil) dan Korem 072/Pamungkas (Sipamungkas) sebagai kasus staf: peta jalan, use case andal, tiga meja, data/hukum, dan deliverable rapat — tanpa menjalankan agen hidup.',
                'long_description' => 'Open Course gratis dalam Bahasa Indonesia. Ini satu kerangka komprehensif, bukan briefing 90 menit. Kasus berjalannya Tentara Nasional Indonesia Angkatan Darat: hierarki Kodam–Korem–Kodim, dengan dua kesatuan staf yang sudah punya sistem nyata — Korem 074/Warastratama (Sisfomil) dan Korem 072/Pamungkas (Sipamungkas). Kamu akan membedakan adopsi dari belanja tools; menyusun peta jalan empat tahap; menyaring use case staf yang andal (administrasi, rapat, pelatihan — bukan intel atau senjata); memisahkan tugas, komando, dan personel; menautkan data prajurit ke UU 27/2022 PDP dan tata kelola elektronik ke Perpres 95/2018 SPBE; serta memakai NATO Principles of Responsible Use dan DoD AI Ethical Principles sebagai rujukan, bukan slide vendor. Agent AI runtime diajarkan sebagai batas academy, bukan tombol Lesson. Lesson berisi teks, tabel, diagram, dan PDF takeaway. Ini bukan konsol. Menyelesaikan kursus ini tidak membuka Course terbatas.',
                'objectives' => [
                    'Menjelaskan adopsi AI kesatuan sebagai keputusan wewenang TNI AD, bukan pembelian chatbot',
                    'Menyusun peta jalan empat tahap: sikap, fondasi, pilot staf, kelembagaan',
                    'Menyaring use case andal di administrasi personel, rapat, surat, dan pelatihan — menolak intel, senjata, targeting',
                    'Memisahkan tiga meja: tugas, komando, dan personel',
                    'Menautkan data prajurit ke UU 27/2022 PDP dan sistem staf ke Perpres 95/2018 SPBE',
                    'Memakai NATO PRU dan DoD AI Ethical Principles sebagai rujukan, bukan pengganti hukum TNI',
                    'Membawa deliverable rapat: lembar keputusan, filter use case, dan peta jalan 24 bulan, dengan contoh Sisfomil dan Sipamungkas',
                ],
                'prerequisites' => [
                    'Tidak ada prasyarat teknis. Ditujukan untuk pimpinan yang memutuskan — Danrem, Kasrem, staf, Dandim — bukan yang men-deploy.',
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
            ['slug' => 'militer'],
            ['name' => 'Militer']
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
            ->where('lessons.title', 'Peta jalan empat tahap TNI AD')
            ->whereNull('lessons.deleted_at')
            ->first(['lessons.*']);

        $chatbot = Lesson::query()
            ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
            ->where('course_sections.course_id', $course->id)
            ->where('lessons.title', 'Bukan beli chatbot lalu selesai')
            ->whereNull('lessons.deleted_at')
            ->first(['lessons.*']);

        $json = json_encode($intro instanceof Lesson ? $intro->rich_content : []);
        $roadmapJson = json_encode($roadmap instanceof Lesson ? $roadmap->rich_content : []);

        return is_string($json)
            && str_contains($json, 'TNI AD')
            && str_contains($json, 'peta jalan')
            && str_contains($json, 'Korem 074')
            && is_string($roadmapJson)
            && str_contains($roadmapJson, '"label":"Fondasi"')
            && str_contains($roadmapJson, 'Sisfomil')
            && $chatbot instanceof Lesson
            && $chatbot->youtube_url === self::YOUTUBE_AGENTS;
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
            'slug' => 'kuis-wajib-ai-untuk-militer',
            'description' => 'Kuis wajib kerangka adopsi AI TNI AD dengan kasus Korem 074 dan 072. Mengukur peta jalan, use case staf, dan wewenang/risiko — bukan ujian operasi runtime.',
            'instructions' => "Jawab dari Lesson. Pilihan ganda dan benar/salah dinilai otomatis. Jawaban singkat menunggu LMS Admin.\n\nIni mengukur kerangka adopsi: peta jalan, use case, wewenang dan risiko. Bukan ujian men-deploy agen.",
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

        $this->createMultipleChoice($assessment, 'Siapa audiens utama kerangka adopsi AI untuk Militer dengan kasus TNI AD?', [
            ['option_text' => 'Danrem, Kasrem, perwira staf, Dandim, dan pimpinan kesatuan yang memutuskan adopsi', 'is_correct' => true],
            ['option_text' => 'Teknisi yang men-deploy agen di produksi Korem 074', 'is_correct' => false],
            ['option_text' => 'Prajurit yang ingin jadi operator OpenClaw', 'is_correct' => false],
            ['option_text' => 'Vendor yang menjual chatbot kesatuan', 'is_correct' => false],
        ], 1);

        $this->createTrueFalse(
            $assessment,
            'Membeli chatbot berarti kesatuan TNI AD sudah mengadopsi AI.',
            false,
            2,
        );

        $this->createMultipleChoice($assessment, 'Urutan peta jalan empat tahap TNI AD yang benar adalah…', [
            ['option_text' => 'Sikap dan wewenang, fondasi, pilot staf, lalu kelembagaan', 'is_correct' => true],
            ['option_text' => 'Beli tools, wajibkan seluruh Kodim, baru tulis kebijakan', 'is_correct' => false],
            ['option_text' => 'Deploy agen hidup di minggu pertama, lalu rapat Danrem', 'is_correct' => false],
            ['option_text' => 'Copy-paste strategi NATO, lalu unggah arsip Sisfomil ke vendor', 'is_correct' => false],
        ], 3);

        $this->createMultipleChoice($assessment, 'Manakah use case AI yang andal untuk dimulai di Korem?', [
            ['option_text' => 'Administrasi personel Sisfomil, notulen rapat, draf surat, dan pelatihan — dengan manusia mengesahkan', 'is_correct' => true],
            ['option_text' => 'Intelijen, senjata, dan targeting di minggu pertama', 'is_correct' => false],
            ['option_text' => 'Menyalin basis Sisfomil 074 ke Sipamungkas 072 agar AI-nya satu', 'is_correct' => false],
            ['option_text' => 'Chatbot yang mengirim perintah ke Kodim tanpa perwira', 'is_correct' => false],
        ], 4);

        $this->createMultipleChoice($assessment, 'Tiga meja yang tidak boleh dicampur dalam satu perintah kesatuan adalah…', [
            ['option_text' => 'Tugas, komando, dan personel', 'is_correct' => true],
            ['option_text' => 'Marketing, pengadaan, dan humas', 'is_correct' => false],
            ['option_text' => 'Chatbot, agen, dan GPU', 'is_correct' => false],
            ['option_text' => 'Katalog Open Course, Restricted Course, dan konsol', 'is_correct' => false],
        ], 5);

        $this->createMultipleChoice($assessment, 'Dasar hukum Indonesia yang paling langsung mengatur data personel ke vendor AI adalah…', [
            ['option_text' => 'UU 27/2022 tentang Pelindungan Data Pribadi', 'is_correct' => true],
            ['option_text' => 'NATO Principles of Responsible Use', 'is_correct' => false],
            ['option_text' => 'DoD AI Ethical Principles 2020', 'is_correct' => false],
            ['option_text' => 'Perintah intern yang belum ada di Lesson ini', 'is_correct' => false],
        ], 6);

        $this->createTrueFalse(
            $assessment,
            'Menjalankan agen hidup di jaringan dinas adalah langkah wajib minggu pertama peta jalan.',
            false,
            7,
        );

        $this->createMultipleChoice($assessment, 'Siapa yang memegang wewenang menandatangani data personel Sisfomil ke pihak ketiga?', [
            ['option_text' => 'Pimpinan yang disebut di lembar keputusan — Danrem dan/atau Kasrem — bukan operator Sisfomil sendirian', 'is_correct' => true],
            ['option_text' => 'Prajurit yang klik setuju di chatbot', 'is_correct' => false],
            ['option_text' => 'Vendor karena sudah punya kebijakan privasi sendiri', 'is_correct' => false],
            ['option_text' => 'Tutor di Lesson ini', 'is_correct' => false],
        ], 8);

        $this->createMultipleChoice($assessment, 'NATO Principles of Responsible Use (2021, dikukuhkan 2024) menuntut aplikasi AI antara lain…', [
            ['option_text' => 'Use case yang eksplisit, tanggung jawab manusia, dan kemampuan mengelola/menghentikan sistem', 'is_correct' => true],
            ['option_text' => 'Wajib men-deploy agen di setiap Korem dalam tujuh hari', 'is_correct' => false],
            ['option_text' => 'Mengganti UU 34/2004 dengan dokumen NATO', 'is_correct' => false],
            ['option_text' => 'Melatih model dari arsip personel tanpa pemilik', 'is_correct' => false],
        ], 9);

        $this->createManualShortAnswer(
            $assessment,
            'Sebutkan satu risiko TNI AD yang harus disebut di rapat pimpinan sebelum tanda tangan vendor AI, dan siapa yang memegang wewenang atas risiko itu.',
            10,
        );
    }

    private function quizIsCurrent(Assessment $quiz): bool
    {
        $text = $quiz->questions->pluck('question_text')->implode(' ');

        return $quiz->questions->count() >= 8
            && str_contains($text, 'peta jalan')
            && str_contains($text, 'use case')
            && str_contains($text, 'wewenang')
            && str_contains($text, 'risiko');
    }
}
