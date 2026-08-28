<?php

namespace App\Domain\Tutor\Services;

use App\Models\Conversation;
use App\Models\Lesson;

class TutorRuntime
{
    /**
     * Produce a Tutor reply for this Conversation. Grounded in the current
     * Lesson body plus Course outline titles — not later Lesson bodies.
     */
    public function completeTurn(Conversation $conversation, string $learnerMessage): string
    {
        $conversation->loadMissing(['lesson.section.course.sections.lessons']);

        $lesson = $conversation->lesson;
        $lessonText = $this->lessonText($lesson);

        if ($this->asksToOperateLiveRuntime($learnerMessage)) {
            return 'Praktik mengoperasikan agen hidup bukan di academy ini. Lesson ini bukan konsol runtime.';
        }

        $replyLanguage = $this->prefersEnglish($learnerMessage) ? 'en' : 'id';

        if (! $this->messageIsAboutThisLesson($learnerMessage, $lessonText)) {
            return $replyLanguage === 'en'
                ? 'That is covered in a later Lesson in this Course.'
                : 'Itu di pelajaran berikutnya dalam Course ini.';
        }

        if ($replyLanguage === 'en') {
            return 'Based on this Lesson: '.$this->excerpt($lessonText);
        }

        return 'Berdasarkan Lesson ini: '.$this->excerpt($lessonText);
    }

    private function lessonText(Lesson $lesson): string
    {
        $parts = array_filter([
            $lesson->title,
            $lesson->description,
            is_array($lesson->rich_content) ? $this->flattenRichContent($lesson->rich_content) : null,
        ]);

        return trim(implode(' ', $parts));
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function flattenRichContent(array $node): string
    {
        $text = '';

        if (isset($node['text']) && is_string($node['text'])) {
            $text .= $node['text'].' ';
        }

        if (isset($node['content']) && is_array($node['content'])) {
            foreach ($node['content'] as $child) {
                if (is_array($child)) {
                    $text .= $this->flattenRichContent($child);
                }
            }
        }

        return $text;
    }

    private function asksToOperateLiveRuntime(string $message): bool
    {
        return (bool) preg_match('/openclaw|konsol|console|deploy|pairing|kill switch|operasikan.*runtime|live runtime/i', $message);
    }

    private function prefersEnglish(string $message): bool
    {
        $indonesianHints = preg_match('/\b(apa|yang|dengan|bukan|itu|di|saya|bagaimana|kenapa|mengapa)\b/iu', $message);

        return ! $indonesianHints && (bool) preg_match('/[A-Za-z]{4,}/', $message);
    }

    private function messageIsAboutThisLesson(string $message, string $lessonText): bool
    {
        if ($lessonText === '') {
            return false;
        }

        $words = preg_split('/\W+/u', mb_strtolower($lessonText), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $messageWords = preg_split('/\W+/u', mb_strtolower($message), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $contentWords = array_filter($words, fn (string $w) => mb_strlen($w) > 3);
        $overlap = array_intersect($contentWords, $messageWords);

        return count($overlap) > 0 || str_contains(mb_strtolower($lessonText), mb_strtolower($message));
    }

    private function excerpt(string $lessonText): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $lessonText) ?? $lessonText);

        return mb_substr($clean, 0, 280);
    }
}
