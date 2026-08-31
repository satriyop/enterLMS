<?php

namespace App\Domain\Content\Services;

use App\Models\ContentProposal;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AuthorRuntime
{
    /**
     * @return array{reason: string, body_text: string}
     */
    public function propose(ContentProposal $proposal): array
    {
        $runtimeUrl = (string) config('author.runtime_url');

        if ($runtimeUrl === '') {
            throw new RuntimeException('Author runtime is not configured.');
        }

        $apiKey = (string) config('author.runtime_api_key');
        $model = trim((string) config('author.model', 'hermes')) ?: 'hermes';
        $timeout = max(1, (int) config('author.timeout_seconds', 90));
        set_time_limit($timeout + 15);

        $messages = [
            [
                'role' => 'system',
                'content' => $this->grounding($proposal),
            ],
            [
                'role' => 'user',
                'content' => $proposal->instruction,
            ],
        ];

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
            throw new RuntimeException('Author runtime failed.', previous: $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Author runtime failed.');
        }

        $reply = trim((string) data_get($response->json(), 'choices.0.message.content'));

        return $this->parse($reply);
    }

    public function grounding(ContentProposal $proposal): string
    {
        return implode("\n", [
            'You are the EnterLMS Author Agent.',
            'proposal_id: '.(string) $proposal->id,
            'course_id: '.(string) $proposal->course_id,
            'lesson_id: '.(string) $proposal->lesson_id,
            'body_ready: true',
            'body_text:',
            $proposal->grounding_body,
            'Propose a replacement Lesson body from this body_text and the LMS Admin instruction.',
            'Do not publish. Do not enroll. Do not complete. Do not teach a Learner.',
            'Reply with JSON only: {"reason":"...","body_text":"..."} in Bahasa Indonesia.',
        ]);
    }

    /**
     * @return array{reason: string, body_text: string}
     */
    public function parse(string $reply): array
    {
        $clean = trim($reply);

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $clean, $fenced) === 1) {
            $clean = $fenced[1];
        }

        $decoded = json_decode($clean, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Author runtime failed.');
        }

        $reason = trim((string) ($decoded['reason'] ?? ''));
        $bodyText = trim((string) ($decoded['body_text'] ?? ''));

        if ($bodyText === '') {
            throw new RuntimeException('Author runtime failed.');
        }

        return [
            'reason' => $reason !== '' ? $reason : 'Usulan dari Author Agent.',
            'body_text' => $bodyText,
        ];
    }
}
