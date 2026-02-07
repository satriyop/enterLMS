<?php

namespace Database\Seeders;

use App\Models\QuestionBankItem;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    /**
     * Seed the question bank with sample banking/compliance questions.
     */
    public function run(): void
    {
        // Get or create trainers to own the questions
        $trainers = User::where('role', 'trainer')->take(3)->get();
        if ($trainers->isEmpty()) {
            $trainers = User::factory()->trainer()->count(2)->create();
        }

        // Create tags for categorization
        $tags = $this->createTags();

        // Seed questions by category
        $this->seedAntiMoneyLaunderingQuestions($trainers->first(), $tags);
        $this->seedCyberSecurityQuestions($trainers->skip(1)->first() ?? $trainers->first(), $tags);
        $this->seedGovernanceQuestions($trainers->last(), $tags);
        $this->seedGeneralBankingQuestions($trainers->first(), $tags);

        $this->command->info('Question Bank seeded with ' . QuestionBankItem::count() . ' questions.');
    }

    private function createTags(): array
    {
        $tagNames = [
            'AML' => 'Anti Money Laundering',
            'KYC' => 'Know Your Customer',
            'Cyber Security' => 'Keamanan Siber',
            'GRC' => 'Governance Risk Compliance',
            'OJK' => 'Regulasi OJK',
            'Perbankan' => 'Perbankan Umum',
            'Etika' => 'Etika Profesi',
            'Risiko' => 'Manajemen Risiko',
        ];

        $tags = [];
        foreach ($tagNames as $name => $description) {
            $tags[$name] = Tag::firstOrCreate(
                ['slug' => str($name)->slug()],
                ['name' => $name]
            );
        }

        return $tags;
    }

    private function seedAntiMoneyLaunderingQuestions(User $owner, array $tags): void
    {
        $questions = [
            [
                'question_text' => 'Apa yang dimaksud dengan Customer Due Diligence (CDD)?',
                'question_type' => 'multiple_choice',
                'default_points' => 2,
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'feedback' => 'CDD adalah proses verifikasi identitas nasabah sebelum membuka rekening atau melakukan transaksi.',
                'options' => [
                    ['option_text' => 'Proses verifikasi identitas dan profil risiko nasabah', 'is_correct' => true],
                    ['option_text' => 'Proses audit internal bank', 'is_correct' => false],
                    ['option_text' => 'Prosedur penutupan rekening', 'is_correct' => false],
                    ['option_text' => 'Metode perhitungan bunga', 'is_correct' => false],
                ],
                'tags' => ['AML', 'KYC'],
            ],
            [
                'question_text' => 'Berapa batas nilai transaksi tunai yang wajib dilaporkan ke PPATK?',
                'question_type' => 'multiple_choice',
                'default_points' => 2,
                'difficulty_level' => 'intermediate',
                'visibility' => 'public',
                'feedback' => 'Sesuai POJK, transaksi tunai senilai Rp 500 juta atau lebih wajib dilaporkan.',
                'options' => [
                    ['option_text' => 'Rp 100 juta', 'is_correct' => false],
                    ['option_text' => 'Rp 500 juta', 'is_correct' => true],
                    ['option_text' => 'Rp 1 miliar', 'is_correct' => false],
                    ['option_text' => 'Rp 250 juta', 'is_correct' => false],
                ],
                'tags' => ['AML', 'OJK'],
            ],
            [
                'question_text' => 'Enhanced Due Diligence (EDD) wajib dilakukan untuk nasabah dengan profil risiko tinggi.',
                'question_type' => 'true_false',
                'default_points' => 1,
                'difficulty_level' => 'beginner',
                'visibility' => 'department',
                'correct_answer' => 'true',
                'feedback' => 'Benar. EDD adalah prosedur pemeriksaan yang lebih mendalam untuk nasabah berisiko tinggi.',
                'tags' => ['AML', 'KYC'],
            ],
            [
                'question_text' => 'Sebutkan 3 indikator transaksi mencurigakan yang harus dilaporkan!',
                'question_type' => 'essay',
                'default_points' => 5,
                'difficulty_level' => 'advanced',
                'visibility' => 'department',
                'grading_rubric' => "Kriteria penilaian:\n- Menyebutkan 3 indikator: 3 poin\n- Penjelasan tepat: 2 poin\n\nContoh jawaban:\n1. Transaksi tidak sesuai profil nasabah\n2. Transaksi dengan pola tidak wajar\n3. Transaksi dari/ke negara berisiko tinggi",
                'tags' => ['AML'],
            ],
            [
                'question_text' => 'Apa kepanjangan dari PPATK?',
                'question_type' => 'short_answer',
                'default_points' => 1,
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'correct_answer' => 'Pusat Pelaporan dan Analisis Transaksi Keuangan',
                'case_sensitive' => false,
                'tags' => ['AML', 'OJK'],
            ],
        ];

        $this->createQuestions($owner, $questions, $tags);
    }

    private function seedCyberSecurityQuestions(User $owner, array $tags): void
    {
        $questions = [
            [
                'question_text' => 'Apa yang dimaksud dengan phishing?',
                'question_type' => 'multiple_choice',
                'default_points' => 2,
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'feedback' => 'Phishing adalah upaya penipuan untuk mendapatkan informasi sensitif dengan menyamar sebagai entitas terpercaya.',
                'options' => [
                    ['option_text' => 'Upaya penipuan melalui email/pesan palsu untuk mencuri data', 'is_correct' => true],
                    ['option_text' => 'Teknik enkripsi data', 'is_correct' => false],
                    ['option_text' => 'Metode backup data', 'is_correct' => false],
                    ['option_text' => 'Prosedur reset password', 'is_correct' => false],
                ],
                'tags' => ['Cyber Security'],
            ],
            [
                'question_text' => 'Password yang kuat sebaiknya mengandung kombinasi huruf besar, huruf kecil, angka, dan simbol.',
                'question_type' => 'true_false',
                'default_points' => 1,
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'correct_answer' => 'true',
                'feedback' => 'Benar. Kombinasi karakter yang beragam membuat password lebih sulit ditebak.',
                'tags' => ['Cyber Security'],
            ],
            [
                'question_text' => 'Apa tindakan pertama yang harus dilakukan jika mencurigai adanya serangan ransomware?',
                'question_type' => 'multiple_choice',
                'default_points' => 3,
                'difficulty_level' => 'intermediate',
                'visibility' => 'department',
                'feedback' => 'Memutus koneksi jaringan mencegah penyebaran ransomware ke sistem lain.',
                'options' => [
                    ['option_text' => 'Membayar tebusan segera', 'is_correct' => false],
                    ['option_text' => 'Memutus koneksi jaringan dan melaporkan ke tim IT Security', 'is_correct' => true],
                    ['option_text' => 'Mematikan komputer dan mengabaikan', 'is_correct' => false],
                    ['option_text' => 'Menghapus semua file', 'is_correct' => false],
                ],
                'tags' => ['Cyber Security'],
            ],
            [
                'question_text' => 'Jelaskan perbedaan antara autentikasi dua faktor (2FA) dan autentikasi multi-faktor (MFA)!',
                'question_type' => 'essay',
                'default_points' => 4,
                'difficulty_level' => 'intermediate',
                'visibility' => 'department',
                'grading_rubric' => "Kriteria:\n- Definisi 2FA (2 poin)\n- Definisi MFA (2 poin)\n\n2FA: Menggunakan tepat 2 faktor autentikasi\nMFA: Menggunakan 2 atau lebih faktor (something you know, have, are)",
                'tags' => ['Cyber Security'],
            ],
            [
                'question_text' => 'Berapa lama maksimal idle session yang direkomendasikan untuk aplikasi perbankan?',
                'question_type' => 'multiple_choice',
                'default_points' => 2,
                'difficulty_level' => 'advanced',
                'visibility' => 'private',
                'feedback' => 'Best practice untuk aplikasi perbankan adalah 5-15 menit idle timeout.',
                'options' => [
                    ['option_text' => '5-15 menit', 'is_correct' => true],
                    ['option_text' => '1 jam', 'is_correct' => false],
                    ['option_text' => '24 jam', 'is_correct' => false],
                    ['option_text' => 'Tidak ada batasan', 'is_correct' => false],
                ],
                'tags' => ['Cyber Security', 'Perbankan'],
            ],
        ];

        $this->createQuestions($owner, $questions, $tags);
    }

    private function seedGovernanceQuestions(User $owner, array $tags): void
    {
        $questions = [
            [
                'question_text' => 'Apa fungsi utama dari Dewan Komisaris dalam tata kelola perusahaan?',
                'question_type' => 'multiple_choice',
                'default_points' => 2,
                'difficulty_level' => 'intermediate',
                'visibility' => 'public',
                'feedback' => 'Dewan Komisaris bertugas mengawasi kebijakan dan pelaksanaan tugas Direksi.',
                'options' => [
                    ['option_text' => 'Menjalankan operasional sehari-hari', 'is_correct' => false],
                    ['option_text' => 'Mengawasi kebijakan dan kinerja Direksi', 'is_correct' => true],
                    ['option_text' => 'Menyetujui kredit nasabah', 'is_correct' => false],
                    ['option_text' => 'Mengelola SDM perusahaan', 'is_correct' => false],
                ],
                'tags' => ['GRC', 'OJK'],
            ],
            [
                'question_text' => 'Three Lines of Defense dalam manajemen risiko terdiri dari unit bisnis, manajemen risiko, dan audit internal.',
                'question_type' => 'true_false',
                'default_points' => 1,
                'difficulty_level' => 'intermediate',
                'visibility' => 'public',
                'correct_answer' => 'true',
                'feedback' => 'Benar. Model Three Lines of Defense adalah kerangka kerja standar manajemen risiko.',
                'tags' => ['GRC', 'Risiko'],
            ],
            [
                'question_text' => 'Jelaskan prinsip-prinsip Good Corporate Governance (GCG) menurut OJK!',
                'question_type' => 'essay',
                'default_points' => 5,
                'difficulty_level' => 'advanced',
                'visibility' => 'department',
                'grading_rubric' => "5 Prinsip GCG (masing-masing 1 poin):\n1. Transparency (Keterbukaan)\n2. Accountability (Akuntabilitas)\n3. Responsibility (Pertanggungjawaban)\n4. Independency (Kemandirian)\n5. Fairness (Kewajaran)",
                'tags' => ['GRC', 'OJK'],
            ],
            [
                'question_text' => 'Apa yang dimaksud dengan conflict of interest dalam konteks perbankan?',
                'question_type' => 'multiple_choice',
                'default_points' => 2,
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'feedback' => 'Conflict of interest terjadi ketika kepentingan pribadi berbenturan dengan kepentingan profesional.',
                'options' => [
                    ['option_text' => 'Benturan antara kepentingan pribadi dan kepentingan perusahaan/nasabah', 'is_correct' => true],
                    ['option_text' => 'Konflik antar departemen', 'is_correct' => false],
                    ['option_text' => 'Perbedaan pendapat dalam rapat', 'is_correct' => false],
                    ['option_text' => 'Persaingan antar bank', 'is_correct' => false],
                ],
                'tags' => ['GRC', 'Etika'],
            ],
        ];

        $this->createQuestions($owner, $questions, $tags);
    }

    private function seedGeneralBankingQuestions(User $owner, array $tags): void
    {
        $questions = [
            [
                'question_text' => 'Apa yang dimaksud dengan Lembaga Penjamin Simpanan (LPS)?',
                'question_type' => 'multiple_choice',
                'default_points' => 2,
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'feedback' => 'LPS adalah lembaga yang menjamin simpanan nasabah di bank.',
                'options' => [
                    ['option_text' => 'Lembaga yang menjamin simpanan nasabah bank', 'is_correct' => true],
                    ['option_text' => 'Lembaga yang mengatur suku bunga', 'is_correct' => false],
                    ['option_text' => 'Lembaga yang menerbitkan uang', 'is_correct' => false],
                    ['option_text' => 'Lembaga yang mengawasi pasar modal', 'is_correct' => false],
                ],
                'tags' => ['Perbankan', 'OJK'],
            ],
            [
                'question_text' => 'Berapa nilai maksimum simpanan yang dijamin oleh LPS?',
                'question_type' => 'multiple_choice',
                'default_points' => 2,
                'difficulty_level' => 'intermediate',
                'visibility' => 'public',
                'feedback' => 'LPS menjamin simpanan nasabah hingga Rp 2 miliar per nasabah per bank.',
                'options' => [
                    ['option_text' => 'Rp 500 juta', 'is_correct' => false],
                    ['option_text' => 'Rp 1 miliar', 'is_correct' => false],
                    ['option_text' => 'Rp 2 miliar', 'is_correct' => true],
                    ['option_text' => 'Tidak ada batasan', 'is_correct' => false],
                ],
                'tags' => ['Perbankan'],
            ],
            [
                'question_text' => 'Bank wajib menjaga kerahasiaan data nasabah sesuai dengan Undang-Undang Perbankan.',
                'question_type' => 'true_false',
                'default_points' => 1,
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'correct_answer' => 'true',
                'feedback' => 'Benar. Rahasia bank adalah salah satu prinsip fundamental dalam perbankan.',
                'tags' => ['Perbankan', 'Etika'],
            ],
            [
                'question_text' => 'Apa perbedaan antara Bank Umum dan Bank Perkreditan Rakyat (BPR)?',
                'question_type' => 'essay',
                'default_points' => 4,
                'difficulty_level' => 'intermediate',
                'visibility' => 'department',
                'grading_rubric' => "Kriteria:\n- Definisi Bank Umum (1 poin)\n- Definisi BPR (1 poin)\n- Perbedaan layanan (1 poin)\n- Perbedaan cakupan (1 poin)\n\nBank Umum: Dapat melakukan semua jenis layanan perbankan termasuk giro\nBPR: Tidak dapat memberikan jasa lalu lintas pembayaran (giro)",
                'tags' => ['Perbankan', 'OJK'],
            ],
            [
                'question_text' => 'Apa kepanjangan dari BMPK dalam konteks perbankan?',
                'question_type' => 'short_answer',
                'default_points' => 1,
                'difficulty_level' => 'intermediate',
                'visibility' => 'public',
                'correct_answer' => 'Batas Maksimum Pemberian Kredit',
                'case_sensitive' => false,
                'tags' => ['Perbankan', 'Risiko'],
            ],
        ];

        $this->createQuestions($owner, $questions, $tags);
    }

    private function createQuestions(User $owner, array $questions, array $allTags): void
    {
        foreach ($questions as $data) {
            $questionTags = $data['tags'] ?? [];
            $options = $data['options'] ?? [];
            unset($data['tags'], $data['options']);

            $item = QuestionBankItem::create([
                'user_id' => $owner->id,
                ...$data,
            ]);

            // Add options for multiple choice questions
            foreach ($options as $index => $option) {
                $item->options()->create([
                    'option_text' => $option['option_text'],
                    'is_correct' => $option['is_correct'],
                    'feedback' => $option['feedback'] ?? null,
                    'order' => $index,
                ]);
            }

            // Attach tags
            $tagIds = collect($questionTags)
                ->map(fn ($name) => $allTags[$name]->id ?? null)
                ->filter()
                ->toArray();

            if (! empty($tagIds)) {
                $item->tags()->attach($tagIds);
            }
        }
    }
}
