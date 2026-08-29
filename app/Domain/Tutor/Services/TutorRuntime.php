<?php

namespace App\Domain\Tutor\Services;

use App\Models\Conversation;
use App\Models\ConversationTurn;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class TutorRuntime
{
    /**
     * Produce a Tutor reply for this Conversation by invoking local Hermes.
     * Grounding lives in the tutor skill + tutor.read MCP — not PHP regex.
     */
    public function completeTurn(Conversation $conversation, string $learnerMessage): string
    {
        $conversation->loadMissing(['turns', 'enrollment']);

        $runtimeUrl = (string) config('tutor.runtime_url');

        if ($runtimeUrl !== '') {
            return $this->completeTurnViaHttp($conversation, $learnerMessage, $runtimeUrl);
        }

        return $this->completeTurnViaCli($conversation, $learnerMessage);
    }

    public function completeTurnViaCli(Conversation $conversation, string $learnerMessage): string
    {
        $conversation->loadMissing(['turns', 'enrollment']);

        $binary = (string) config('tutor.hermes_binary');

        if ($binary === '' || ! is_file($binary)) {
            throw new RuntimeException('Tutor runtime is not configured.');
        }

        $session = 'enterlms-conversation-'.$conversation->id;
        $skill = (string) config('tutor.skill', 'tutor');
        $timeout = max(1, (int) config('tutor.timeout_seconds', 90));
        $maxTurns = max(1, (int) config('tutor.max_turns', 8));
        $this->allowRuntimeWait($timeout);

        $result = Process::timeout($timeout)
            ->input($this->prompt($conversation, $learnerMessage))
            ->run([
                $binary,
                'chat',
                '-Q',
                '--query-file',
                '-',
                '--skills',
                $skill,
                '--continue',
                $session,
                '--create-if-missing',
                '--source',
                'enterlms-tutor',
                '--max-turns',
                (string) $maxTurns,
            ]);

        if (! $result->successful()) {
            throw new RuntimeException('Tutor runtime failed.');
        }

        $reply = $this->sanitize($result->output());

        if ($reply === '') {
            throw new RuntimeException('Tutor runtime failed.');
        }

        return $reply;
    }

    private function completeTurnViaHttp(Conversation $conversation, string $learnerMessage, string $runtimeUrl): string
    {
        $secret = (string) config('tutor.runtime_secret');
        $timeout = max(1, (int) config('tutor.timeout_seconds', 90));
        $this->allowRuntimeWait($timeout);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(5)
                ->acceptJson()
                ->withHeaders(['X-Tutor-Runtime-Secret' => $secret])
                ->post(rtrim($runtimeUrl, '/').'/complete-turn', [
                    'conversation_id' => $conversation->id,
                    'message' => $learnerMessage,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Tutor runtime failed.', previous: $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Tutor runtime failed.');
        }

        $reply = trim((string) $response->json('reply'));

        if ($reply === '') {
            throw new RuntimeException('Tutor runtime failed.');
        }

        return $reply;
    }

    private function allowRuntimeWait(int $timeout): void
    {
        set_time_limit($timeout + 15);
    }

    private function prompt(Conversation $conversation, string $learnerMessage): string
    {
        $courseId = $conversation->enrollment->course_id;

        $lines = [
            'Conversation id: '.$conversation->id,
            'Course id: '.(string) $courseId,
            'Lesson id: '.$conversation->lesson_id,
            'Enrollment id: '.$conversation->enrollment_id,
            '',
            'Call get-published-lesson and get-course-outline with this course_id.',
            '',
            'History:',
        ];

        foreach ($conversation->turns as $turn) {
            $role = $turn->role === ConversationTurn::ROLE_TUTOR ? 'Tutor' : 'Learner';
            $lines[] = $role.': '.$turn->body;
        }

        $lines[] = '';
        $lines[] = 'Learner: '.$learnerMessage;

        return implode("\n", $lines);
    }

    private function sanitize(string $output): string
    {
        $clean = trim(preg_replace('/\e\[[0-9;]*m/', '', $output) ?? $output);

        $lines = preg_split("/\r\n|\n|\r/", $clean) ?: [];
        $kept = [];
        foreach ($lines as $line) {
            if (preg_match('/^session_id:/i', $line) === 1 || preg_match('/^Session .+ found/i', $line) === 1) {
                break;
            }
            $kept[] = $line;
        }
        $clean = trim(implode("\n", $kept));

        if ($clean === '') {
            return '';
        }

        if (str_contains(mb_strtolower($clean), 'traceback')) {
            throw new RuntimeException('Tutor runtime failed.');
        }

        return $clean;
    }
}
