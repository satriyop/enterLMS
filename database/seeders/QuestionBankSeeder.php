<?php

namespace Database\Seeders;

use App\Models\QuestionBankItem;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    /**
     * Seed the question bank with academy questions.
     */
    public function run(): void
    {
        $trainers = User::where('role', 'trainer')->take(3)->get();
        if ($trainers->isEmpty()) {
            $trainers = User::factory()->trainer()->count(2)->create();
        }

        $tags = $this->createTags();

        $this->seedIntroQuestions($trainers->first(), $tags);
        $this->seedOperatorQuestions($trainers->skip(1)->first() ?? $trainers->first(), $tags);

        $this->command->info('Question Bank seeded with '.QuestionBankItem::count().' questions.');
    }

    /**
     * @return array<string, Tag>
     */
    private function createTags(): array
    {
        $tagNames = [
            'Agen AI' => 'Agen AI',
            'OpenClaw' => 'OpenClaw',
            'Enteraksi' => 'Enteraksi',
            'Operator' => 'Operator',
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

    /**
     * @param  array<string, Tag>  $tags
     */
    private function seedIntroQuestions(User $owner, array $tags): void
    {
        $questions = [
            [
                'question_text' => 'Apa peran EnterLMS dalam keluarga produk AI ini?',
                'question_type' => 'multiple_choice',
                'default_points' => 2,
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'feedback' => 'EnterLMS adalah academy, bukan control plane.',
                'options' => [
                    ['option_text' => 'Academy untuk belajar menjalankan dan membangun produk agen', 'is_correct' => true],
                    ['option_text' => 'Control plane untuk men-deploy OpenClaw', 'is_correct' => false],
                    ['option_text' => 'Lab GPU untuk melatih model dari nol', 'is_correct' => false],
                    ['option_text' => 'Dashboard trading saham', 'is_correct' => false],
                ],
                'tags' => ['Agen AI', 'Enteraksi'],
            ],
            [
                'question_text' => 'Siapa pun yang mendaftar boleh menjadi Learner pada Open Course.',
                'question_type' => 'true_false',
                'default_points' => 1,
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'correct_answer' => 'true',
                'feedback' => 'Benar. Learner bukan sinonim akun Enteraksi.',
                'tags' => ['Agen AI'],
            ],
            [
                'question_text' => 'Menyelesaikan Pengenalan Agen AI membuka akses Administrasi OpenClaw untuk publik.',
                'question_type' => 'true_false',
                'default_points' => 1,
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'correct_answer' => 'false',
                'feedback' => 'Salah. Course terbatas hanya diberikan LMS Admin lewat Learning Path.',
                'tags' => ['Agen AI', 'OpenClaw'],
            ],
        ];

        $this->createQuestions($owner, $questions, $tags);
    }

    /**
     * @param  array<string, Tag>  $tags
     */
    private function seedOperatorQuestions(User $owner, array $tags): void
    {
        $questions = [
            [
                'question_text' => 'Apa yang dilakukan Operator saat memakai kill switch?',
                'question_type' => 'multiple_choice',
                'default_points' => 2,
                'difficulty_level' => 'intermediate',
                'visibility' => 'public',
                'feedback' => 'Kill switch memotong lalu lintas berbahaya, lalu dilaporkan.',
                'options' => [
                    ['option_text' => 'Memotong lalu lintas berbahaya per konektor atau tenant', 'is_correct' => true],
                    ['option_text' => 'Mengubah kebijakan knowledge tenant', 'is_correct' => false],
                    ['option_text' => 'Menulis skill baru di academy', 'is_correct' => false],
                    ['option_text' => 'Menggabungkan memori dua tenant', 'is_correct' => false],
                ],
                'tags' => ['OpenClaw', 'Operator'],
            ],
            [
                'question_text' => 'Operator boleh memutuskan kebijakan bisnis tenant.',
                'question_type' => 'true_false',
                'default_points' => 1,
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'correct_answer' => 'false',
                'feedback' => 'Operator menjalankan Deployment, bukan keputusan bisnis tenant.',
                'tags' => ['Operator', 'Enteraksi'],
            ],
        ];

        $this->createQuestions($owner, $questions, $tags);
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @param  array<string, Tag>  $allTags
     */
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

            foreach ($options as $index => $option) {
                $item->options()->create([
                    'option_text' => $option['option_text'],
                    'is_correct' => $option['is_correct'],
                    'feedback' => $option['feedback'] ?? null,
                    'order' => $index,
                ]);
            }

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
