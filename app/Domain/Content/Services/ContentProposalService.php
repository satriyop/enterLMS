<?php

namespace App\Domain\Content\Services;

use App\Models\ContentProposal;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ContentProposalService
{
    public function __construct(
        protected AuthorRuntime $runtime,
    ) {}

    public function ask(User $admin, Course $course, Lesson $lesson, string $instruction): ContentProposal
    {
        $lesson->loadMissing(['section', 'media']);

        if ($lesson->section->course_id !== $course->id) {
            throw new RuntimeException('Pelajaran tidak termasuk dalam Course ini.');
        }

        if ($lesson->content_type !== 'text') {
            throw new RuntimeException('Usulan konten hanya untuk Lesson teks.');
        }

        if (! $lesson->isBodyReady()) {
            throw new RuntimeException('Isi Lesson belum siap dibaca Author Agent.');
        }

        $proposal = ContentProposal::query()->create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'asked_by' => $admin->id,
            'instruction' => $instruction,
            'grounding_body' => $lesson->readableBody(),
            'status' => ContentProposal::STATUS_ASKING,
        ]);

        try {
            $draft = $this->runtime->propose($proposal);
        } catch (Throwable $e) {
            Log::warning('Author runtime failed.', [
                'content_proposal_id' => $proposal->id,
                'error' => $e->getMessage(),
            ]);
            $proposal->markFailed();

            throw new RuntimeException('Author runtime failed.', previous: $e);
        }

        return $this->recordDraft(
            $proposal->fresh() ?? $proposal,
            $draft['body_text'],
            $draft['reason'],
        );
    }

    public function recordDraft(ContentProposal $proposal, string $bodyText, string $reason): ContentProposal
    {
        $bodyText = trim($bodyText);

        if ($bodyText === '') {
            throw new RuntimeException('Usulan konten tidak memiliki isi.');
        }

        return $proposal->recordDraft(
            $bodyText,
            $this->docFromBodyText($bodyText),
            trim($reason) !== '' ? trim($reason) : 'Usulan dari Author Agent.',
        );
    }

    /**
     * @return array{type: string, content: list<array<string, mixed>>}
     */
    public function docFromBodyText(string $text): array
    {
        $paragraphs = preg_split("/\n\s*\n/", trim($text)) ?: [trim($text)];
        $content = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            $content[] = [
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => $paragraph]],
            ];
        }

        if ($content === []) {
            throw new RuntimeException('Usulan konten tidak memiliki isi.');
        }

        return [
            'type' => 'doc',
            'content' => $content,
        ];
    }
}
