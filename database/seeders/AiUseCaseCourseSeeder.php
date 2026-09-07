<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Tag;
use App\Models\User;
use App\Services\SeederThumbnailGenerator;
use Database\Seeders\Concerns\BuildsAcademyLessonContent;
use Database\Seeders\Concerns\BuildsAiUseCaseSyllabus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Open catalog Course: building an AI model for one specific use case.
 *
 *   php artisan db:seed --class=AiUseCaseCourseSeeder
 */
class AiUseCaseCourseSeeder extends Seeder
{
    use BuildsAcademyLessonContent;
    use BuildsAiUseCaseSyllabus;

    public const COURSE_TITLE = 'Membangun Model AI untuk Kasus Tertentu';

    public const QUIZ_TITLE = 'Kuis Wajib Membangun Model AI untuk Kasus Tertentu';

    public const PREVIEW_LESSON_TITLE = 'Untuk siapa kursus ini';

    /**
     * @var list<string>
     */
    public const SECTION_TITLES = [
        'Merangka kasus dan metrik sukses',
        'Data untuk kasus itu',
        'Memilih model untuk kasus itu',
        'Melatih dan mengadaptasi',
        'Mengevaluasi terhadap metrik kasus',
        'Melayani dan mengiterasi',
    ];

    /**
     * @var list<string>
     */
    public const LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'Kasus yang kita bangun: klasifikasi tiket layanan',
        'Masalah bisnis bukan masalah model',
        'Metrik sukses yang selaras dengan kasus',
        'Kapan kasus ini tidak butuh model',
        'Sumber tiket dan apa yang boleh dipakai',
        'Ontologi label dan panduan anotasi',
        'Mengumpulkan, menyeimbangkan, dan membersihkan',
        'Split temporal: latih, validasi, uji',
        'Kebocoran data yang merusak metrik',
        'Baseline dulu: aturan dan model linear',
        'Tangga model: dari TF-IDF ke encoder',
        'Klasifikasi, ekstraksi, atau generasi',
        'Bahasa Indonesia, slang, dan kode tiket',
        'Biaya, latensi, dan interpretabilitas',
        'Representasi teks tiket',
        'Menyesuaikan encoder, bukan memaksa model generatif',
        'Loop pelatihan: loss, batch, overfitting',
        'Kelas timpang dan biaya salah routing',
        'Reproduksibilitas dan early stopping',
        'Confusion matrix lima kelas tiket',
        'Macro-F1, bukan akurasi global',
        'Ambang tidak yakin dan eskalasi manusia',
        'Analisis galat: tiket campur dan bahasa campur',
        'Uji temporal di data yang lebih baru',
        'Kontrak serving: dari teks ke label',
        'Mode bayangan dan peluncuran bertahap',
        'Umpan balik petugas dan putaran ulang',
        'Privasi tiket, bias, dan audit',
        'Ringkasan: dari kasus ke model yang hidup',
    ];

    /**
     * Intro and deepdive Lessons that must render a visual figure.
     *
     * @var list<string>
     */
    public const DIAGRAM_LESSON_TITLES = [
        'Untuk siapa kursus ini',
        'Kasus yang kita bangun: klasifikasi tiket layanan',
        'Metrik sukses yang selaras dengan kasus',
        'Split temporal: latih, validasi, uji',
        'Kebocoran data yang merusak metrik',
        'Baseline dulu: aturan dan model linear',
        'Tangga model: dari TF-IDF ke encoder',
        'Loop pelatihan: loss, batch, overfitting',
        'Confusion matrix lima kelas tiket',
        'Ambang tidak yakin dan eskalasi manusia',
        'Kontrak serving: dari teks ke label',
        'Umpan balik petugas dan putaran ulang',
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
            ['slug' => 'membangun-model-ai'],
            [
                'name' => 'Membangun Model AI',
                'description' => 'Kursus terbuka tentang merancang, melatih, dan mengevaluasi model AI untuk satu kasus nyata.',
                'order' => 6,
            ]
        );
    }

    private function seedCourse(User $admin, Category $category): Course
    {
        $thumbnailPath = app(SeederThumbnailGenerator::class)->generate(
            self::COURSE_TITLE,
            'courses/thumbnails',
            'membangun-model-ai-kasus-tertentu.jpg'
        );

        $course = Course::query()->updateOrCreate(
            ['title' => self::COURSE_TITLE],
            [
                'user_id' => $admin->id,
                'slug' => Str::slug(self::COURSE_TITLE),
                'short_description' => 'Kursus mendalam: dari merangka kasus klasifikasi tiket layanan sampai model, evaluasi, serving, dan iterasi — tanpa lab hidup.',
                'long_description' => 'Open Course gratis dalam Bahasa Indonesia. Kamu akan membangun pemahaman lengkap tentang satu kasus: klasifikasi tiket layanan di organisasi Indonesia. Bukan survei generik machine learning. Setiap bagian memakai kasus yang sama: merangka keputusan dan metrik, menyiapkan data tanpa bocor, memilih model, melatih, mengevaluasi terhadap metrik kasus, lalu serving dan iterasi. Lesson berisi teks dan diagram. Ini bukan konsol pelatihan dan bukan Course terbatas. Menyelesaikannya tidak membuka Administrasi Agen OpenClaw.',
                'objectives' => [
                    'Merangka satu kasus AI sebagai keputusan bisnis dengan metrik yang bisa diukur',
                    'Menyusun data, label, split, dan pencegahan kebocoran untuk kasus itu',
                    'Memilih model yang sesuai tugas, bahasa, biaya, dan latensi — bukan yang paling baru',
                    'Menjelaskan loop pelatihan, kelas timpang, dan early stopping tanpa menjalankan lab',
                    'Mengevaluasi dengan macro-F1, confusion matrix, dan ambang eskalasi manusia',
                    'Merancang serving, mode bayangan, umpan balik petugas, privasi, dan putaran ulang',
                ],
                'prerequisites' => [
                    'Nyaman membaca teks teknis dalam Bahasa Indonesia. Tidak wajib pernah menulis kode, tetapi Lesson membahas representasi teks, metrik, dan trade-off model secara mendalam.',
                ],
                'category_id' => $category->id,
                'thumbnail_path' => $thumbnailPath,
                'status' => 'published',
                'visibility' => 'public',
                'difficulty_level' => 'intermediate',
                'estimated_duration_minutes' => 480,
                'is_paid' => false,
                'price' => null,
                'published_at' => now(),
                'published_by' => $admin->id,
            ]
        );

        $tag = Tag::query()->firstOrCreate(
            ['slug' => 'kasus-penggunaan'],
            ['name' => 'Kasus Penggunaan']
        );
        $course->tags()->syncWithoutDetaching([$tag->id]);

        if ($this->catalogMatches($course, self::LESSON_TITLES)) {
            $this->command?->info('Open course catalog already current: '.self::COURSE_TITLE);

            return $course->load('sections.lessons');
        }

        $this->replaceCourseLessons($course, $this->sections());

        return $course->load('sections.lessons');
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
            'slug' => 'kuis-wajib-membangun-model-ai-untuk-kasus-tertentu',
            'description' => 'Kuis wajib tentang pipeline membangun model untuk klasifikasi tiket layanan. Bukan ujian menjalankan lab.',
            'instructions' => "Jawab dari Lesson. Pilihan ganda dan benar/salah dinilai otomatis. Jawaban singkat menunggu LMS Admin.\n\nIni mengukur pemahaman kasus, data, model, pelatihan, evaluasi, dan serving — bukan kemampuan men-deploy runtime.",
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

        $this->createMultipleChoice($assessment, 'Langkah pertama yang benar saat membangun model untuk kasus tertentu adalah…', [
            ['option_text' => 'Merangka keputusan yang akan diambil model dan metrik sukses yang selaras dengan kasus', 'is_correct' => true],
            ['option_text' => 'Memilih arsitektur encoder terbaru lalu mencari data yang cocok', 'is_correct' => false],
            ['option_text' => 'Men-deploy model generatif ke produksi agar terlihat modern', 'is_correct' => false],
            ['option_text' => 'Membuka konsol runtime di dalam Lesson', 'is_correct' => false],
        ], 1);

        $this->createTrueFalse(
            $assessment,
            'Akurasi global cukup sebagai metrik sukses klasifikasi tiket layanan yang kelasnya timpang.',
            false,
            2,
        );

        $this->createMultipleChoice($assessment, 'Split data yang paling aman untuk tiket layanan yang datang berurutan waktu adalah…', [
            ['option_text' => 'Split temporal: latih di periode lama, uji di periode yang lebih baru', 'is_correct' => true],
            ['option_text' => 'Acak 80/20 tanpa memperhatikan tanggal tiket', 'is_correct' => false],
            ['option_text' => 'Pakai tiket yang sama di latih dan uji supaya akurasi tinggi', 'is_correct' => false],
            ['option_text' => 'Hanya uji pada tiket yang petugasnya sudah tahu jawabannya', 'is_correct' => false],
        ], 3);

        $this->createMultipleChoice($assessment, 'Contoh kebocoran data pada kasus tiket layanan adalah…', [
            ['option_text' => 'Kolom nama petugas, nomor tiket, atau teks template otomatis yang tidak akan ada saat prediksi hidup', 'is_correct' => true],
            ['option_text' => 'Membersihkan typo sebelum membagi data', 'is_correct' => false],
            ['option_text' => 'Menyimpan panduan label untuk anotator', 'is_correct' => false],
            ['option_text' => 'Mengukur kesepakatan antar-anotator', 'is_correct' => false],
        ], 4);

        $this->createTrueFalse(
            $assessment,
            'Model generatif berukuran besar adalah pilihan pertama yang tepat untuk klasifikasi lima kelas tiket layanan.',
            false,
            5,
        );

        $this->createMultipleChoice($assessment, 'Mengapa baseline aturan + TF-IDF + regresi logistik wajib ada sebelum encoder?', [
            ['option_text' => 'Supaya kamu tahu berapa yang sudah bisa diselesaikan tanpa model berat, dan punya pembanding yang jujur', 'is_correct' => true],
            ['option_text' => 'Karena encoder tidak bisa membaca Bahasa Indonesia', 'is_correct' => false],
            ['option_text' => 'Karena aturan selalu mengalahkan model terlatih', 'is_correct' => false],
            ['option_text' => 'Karena Lesson ini adalah lab untuk menjalankan regresi', 'is_correct' => false],
        ], 6);

        $this->createMultipleChoice($assessment, 'Metrik evaluasi yang paling selaras dengan tiket timpang (misalnya kelas perangkat jarang) adalah…', [
            ['option_text' => 'Macro-F1 plus recall per kelas, terutama kelas yang mahal jika terlewat', 'is_correct' => true],
            ['option_text' => 'Akurasi global saja karena mudah dijelaskan ke pimpinan', 'is_correct' => false],
            ['option_text' => 'Loss pelatihan di epoch terakhir', 'is_correct' => false],
            ['option_text' => 'Jumlah parameter model', 'is_correct' => false],
        ], 7);

        $this->createMultipleChoice($assessment, 'Fungsi ambang “tidak yakin” pada serving klasifikasi tiket adalah…', [
            ['option_text' => 'Mengirim prediksi rendah-keyakinan ke petugas manusia, bukan memaksa label', 'is_correct' => true],
            ['option_text' => 'Menghapus tiket dari antrian', 'is_correct' => false],
            ['option_text' => 'Menaikkan akurasi global dengan membuang data uji', 'is_correct' => false],
            ['option_text' => 'Mengganti Restricted Course menjadi Open Course', 'is_correct' => false],
        ], 8);

        $this->createMultipleChoice($assessment, 'Mode bayangan (shadow) sebelum peluncuran penuh berarti…', [
            ['option_text' => 'Model memprediksi di paralel dengan routing lama, tanpa mengubah tiket pengguna dulu', 'is_correct' => true],
            ['option_text' => 'Model langsung mengganti seluruh petugas layanan', 'is_correct' => false],
            ['option_text' => 'Lesson menjadi konsol untuk men-deploy agen hidup', 'is_correct' => false],
            ['option_text' => 'Data uji dicampur ke data latih agar terlihat bagus', 'is_correct' => false],
        ], 9);

        $this->createManualShortAnswer(
            $assessment,
            'Sebutkan satu sumber kebocoran data pada klasifikasi tiket layanan dan mengapa sumber itu merusak metrik.',
            10,
        );
    }
}
