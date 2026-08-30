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

        return $this->completeTurnFromPrompt(
            'enterlms-conversation-'.$conversation->id,
            $this->prompt($conversation, $learnerMessage),
        );
    }

    /**
     * Spawn Hermes with a prompt built by Laravel. The runtime host must not
     * load Conversation from its own database (laptop DB ≠ production).
     */
    public function completeTurnFromPrompt(string $session, string $prompt): string
    {
        if (preg_match('/^enterlms-conversation-\d+$/', $session) !== 1) {
            throw new RuntimeException('Tutor runtime is not configured.');
        }

        $binary = (string) config('tutor.hermes_binary');

        if ($binary === '' || ! is_file($binary) || trim($prompt) === '') {
            throw new RuntimeException('Tutor runtime is not configured.');
        }

        $skill = (string) config('tutor.skill', 'tutor');
        $timeout = max(1, (int) config('tutor.timeout_seconds', 90));
        $maxTurns = max(1, (int) config('tutor.max_turns', 8));
        $this->allowRuntimeWait($timeout);

        $command = [$binary];
        $profile = trim((string) config('tutor.hermes_profile', ''));
        if ($profile !== '') {
            if (preg_match('/^[a-z0-9-]+$/', $profile) !== 1) {
                throw new RuntimeException('Tutor runtime is not configured.');
            }
            $command[] = '-p';
            $command[] = $profile;
        }

        $result = Process::timeout($timeout)
            ->input($prompt)
            ->run(array_merge($command, [
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
            ]));

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
        $conversation->loadMissing(['turns', 'enrollment']);

        $apiKey = (string) config('tutor.runtime_api_key');
        $model = trim((string) config('tutor.model', 'hermes')) ?: 'hermes';
        $timeout = max(1, (int) config('tutor.timeout_seconds', 90));
        $this->allowRuntimeWait($timeout);
        $messages = $this->messages($conversation, $learnerMessage);

        try {
            $request = Http::timeout($timeout)
                ->connectTimeout(5)
                ->acceptJson();

            if ($apiKey !== '') {
                $request = $request->withToken($apiKey);
            }

            $response = $request->post(rtrim($runtimeUrl, '/').'/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Tutor runtime failed.', previous: $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Tutor runtime failed.');
        }

        $reply = trim((string) data_get($response->json(), 'choices.0.message.content'));

        if ($reply === '') {
            throw new RuntimeException('Tutor runtime failed.');
        }

        return $reply;
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    public function messages(Conversation $conversation, string $learnerMessage): array
    {
        $conversation->loadMissing(['turns', 'enrollment']);

        $messages = [[
            'role' => 'system',
            'content' => implode("\n", [
                'You are the EnterLMS Tutor.',
                'Learner id: '.(string) $conversation->enrollment->user_id,
                'Course id: '.(string) $conversation->enrollment->course_id,
                'Lesson id: '.$conversation->lesson_id,
                'Conversation id: '.$conversation->id,
                'Call get-published-lesson with this user_id, course_id, and lesson_id.',
            ]),
        ]];

        foreach ($conversation->turns as $turn) {
            $messages[] = [
                'role' => $turn->role === ConversationTurn::ROLE_TUTOR ? 'assistant' : 'user',
                'content' => $turn->body,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $learnerMessage,
        ];

        return $messages;
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
            'Learner id: '.(string) $conversation->enrollment->user_id,
            'Course id: '.(string) $courseId,
            'Lesson id: '.$conversation->lesson_id,
            'Enrollment id: '.$conversation->enrollment_id,
            '',
            'Call get-published-lesson with this user_id, course_id, and lesson_id.',
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
