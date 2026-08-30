<?php

namespace App\Mcp\Tools\Tutor;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Tutor\Services\TutorFocusService;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Mcp\Concerns\RefusesMismatchedChannelIdentity;
use App\Models\TutorFocus;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-focus')]
#[Description('Get the current messaging Focus for a named Learner and skin. Overlay Focus is the Lesson URL and is not stored.')]
#[IsReadOnly]
#[IsIdempotent]
class GetFocusTool extends Tool
{
    use AuditsAgentToolCalls;
    use RefusesMismatchedChannelIdentity;

    public function __construct(
        protected TutorFocusService $focuses,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::TUTOR_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'skin' => ['required', 'string', Rule::in(TutorFocus::skins())],
                ...$this->channelIdentityRules(),
            ], [
                'user_id.required' => 'user_id wajib diisi.',
                'skin.required' => 'skin wajib diisi.',
                'skin.in' => 'skin harus whatsapp atau telegram.',
                ...$this->channelIdentityMessages(),
            ]);

            if ($mismatch = $this->refuseMismatchedChannel($validated)) {
                return $mismatch;
            }

            $learner = User::query()->findOrFail($validated['user_id']);
            $current = $this->focuses->current($learner, $validated['skin']);

            return Response::structured([
                'ok' => true,
                'data' => [
                    'skin' => $validated['skin'],
                    'inferred' => (bool) ($current['inferred'] ?? false),
                    'must_pick' => $current === null,
                    'focus' => $current === null ? null : [
                        'enrollment_id' => $current['enrollment_id'],
                        'course_id' => $current['course_id'],
                        'lesson_id' => $current['lesson_id'],
                        'title' => $current['title'],
                    ],
                ],
            ]);
        });
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('Named Learner')->required(),
            'skin' => $schema->string()->description('whatsapp or telegram')->required(),
        ];
    }
}
