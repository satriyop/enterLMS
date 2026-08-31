<?php

namespace Database\Factories;

use App\Models\ContentProposal;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentProposal>
 */
class ContentProposalFactory extends Factory
{
    protected $model = ContentProposal::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory()->published()->public(),
            'lesson_id' => function (array $attributes): int {
                $section = CourseSection::factory()->create([
                    'course_id' => $attributes['course_id'],
                ]);

                return Lesson::factory()->text()->create([
                    'course_section_id' => $section->id,
                ])->id;
            },
            'asked_by' => User::factory()->lmsAdmin(),
            'instruction' => 'Perjelas bedanya chatbot dan agen.',
            'grounding_body' => 'Agen berbeda dari chatbot biasa.',
            'proposed_rich_content' => null,
            'proposed_body_text' => null,
            'reason' => null,
            'status' => ContentProposal::STATUS_ASKING,
        ];
    }

    public function pending(): static
    {
        $body = 'Chatbot menjawab percakapan. Agen menerima tujuan dan memakai alat.';

        return $this->state(fn (): array => [
            'status' => ContentProposal::STATUS_PENDING,
            'proposed_body_text' => $body,
            'reason' => 'Lesson belum memisahkan chatbot dan agen.',
            'proposed_rich_content' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => $body]],
                ]],
            ],
        ]);
    }

    public function accepted(): static
    {
        return $this->pending()->state(fn (): array => [
            'status' => ContentProposal::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->pending()->state(fn (): array => [
            'status' => ContentProposal::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);
    }
}
