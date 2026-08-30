<?php

namespace App\Mcp\Tools\Tutor;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Tutor\Services\TutorFocusService;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Mcp\Concerns\RefusesMismatchedChannelIdentity;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-focusable-lessons')]
#[Description('List Lessons this named Learner may Focus: enrolled and unlocked. Titles only — no Lesson bodies.')]
#[IsReadOnly]
#[IsIdempotent]
class ListFocusableLessonsTool extends Tool
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
                ...$this->channelIdentityRules(),
            ], [
                'user_id.required' => 'user_id wajib diisi.',
                ...$this->channelIdentityMessages(),
            ]);

            if ($mismatch = $this->refuseMismatchedChannel($validated)) {
                return $mismatch;
            }

            $learner = User::query()->findOrFail($validated['user_id']);

            return Response::structured([
                'ok' => true,
                'data' => [
                    'courses' => $this->focuses->listFocusable($learner),
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
        ];
    }
}
