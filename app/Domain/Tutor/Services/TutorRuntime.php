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
     * Overlay grounding is the Lesson body Laravel already read for this Learner.
     * MCP remains the door for messaging skins, not a substitute for that read.
     */
    public function completeTurn(Conversation $conversation, string $learnerMessage, string $lessonBody = ''): string
    {
        $conversation->loadMissing(['turns', 'enrollment']);

        $runtimeUrl = (string) config('tutor.runtime_url');

        if ($runtimeUrl !== '') {
            return $this->completeTurnViaHttp($conversation, $learnerMessage, $runtimeUrl, $lessonBody);
        }

        return $this->completeTurnViaCli($conversation, $learnerMessage, $lessonBody);
    }

    public function completeTurnViaCli(Conversation $conversation, string $learnerMessage, string $lessonBody = ''): string
    {
        $conversation->loadMissing(['turns', 'enrollment']);

        return $this->completeTurnFromPrompt(
            'enterlms-conversation-'.$conversation->id,
            $this->prompt($conversation, $learnerMessage, $lessonBody),
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

    private function completeTurnViaHttp(Conversation $conversation, string $learnerMessage, string $runtimeUrl, string $lessonBody = ''): string
    {
        $conversation->loadMissing(['turns', 'enrollment']);

        $apiKey = (string) config('tutor.runtime_api_key');
        $model = trim((string) config('tutor.model', 'hermes')) ?: 'hermes';
        $timeout = max(1, (int) config('tutor.timeout_seconds', 90));
        $this->allowRuntimeWait($timeout);
        $messages = $this->messages($conversation, $learnerMessage, $lessonBody);

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
    public function messages(Conversation $conversation, string $learnerMessage, string $lessonBody = ''): array
    {
        $conversation->loadMissing(['turns', 'enrollment']);

        $messages = [[
            'role' => 'system',
            'content' => $this->grounding($conversation, $lessonBody),
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

    private function grounding(Conversation $conversation, string $lessonBody): string
    {
        $lines = [
            'You are the EnterLMS Tutor.',
            'user_id: '.(string) $conversation->enrollment->user_id,
            'course_id: '.(string) $conversation->enrollment->course_id,
            'lesson_id: '.$conversation->lesson_id,
            'conversation_id: '.$conversation->id,
        ];

        if ($lessonBody !== '') {
            $lines[] = 'body_ready: true';
            $lines[] = 'body_text:';
            $lines[] = $lessonBody;
            $lines[] = 'Answer from this body_text. Do not invent a user_id.';
        } else {
            $lines[] = 'Call get-published-lesson with this user_id, course_id, and lesson_id.';
        }

        return implode("\n", $lines);
    }

    private function prompt(Conversation $conversation, string $learnerMessage, string $lessonBody = ''): string
    {
        $lines = [
            $this->grounding($conversation, $lessonBody),
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
