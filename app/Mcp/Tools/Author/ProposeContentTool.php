<?php

namespace App\Mcp\Tools\Author;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Content\Services\ContentProposalService;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\ContentProposal;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

#[Name('propose-content')]
#[Description('Fill a Content Proposal that LMS Admin already asked for. Fail-closed: no asking proposal, no write. Laravel is the only writer.')]
class ProposeContentTool extends Tool
{
    use AuditsAgentToolCalls;

    public function __construct(
        protected ContentProposalService $proposals,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::AUTHOR_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            $user = $request->user();

            if (! $user instanceof User || ! $user->isLmsAdmin()) {
                return Response::error('Hanya LMS Admin yang boleh menulis usulan konten.');
            }

            $validated = $request->validate([
                'proposal_id' => ['required', 'integer', 'exists:content_proposals,id'],
                'body_text' => ['required', 'string', 'min:1', 'max:20000'],
                'reason' => ['nullable', 'string', 'max:4000'],
            ], [
                'proposal_id.required' => 'proposal_id wajib diisi.',
                'body_text.required' => 'Isi usulan wajib diisi.',
            ]);

            $proposal = ContentProposal::query()->findOrFail($validated['proposal_id']);

            if (! $proposal->isAsking()) {
                return Response::error('Tidak ada permintaan usulan yang menunggu. LMS Admin harus bertanya dulu.');
            }

            try {
                $proposal = $this->proposals->recordDraft(
                    $proposal,
                    $validated['body_text'],
                    (string) ($validated['reason'] ?? ''),
                );
            } catch (RuntimeException $e) {
                return Response::error($e->getMessage());
            }

            return Response::structured([
                'ok' => true,
                'data' => [
                    'proposal_id' => $proposal->id,
                    'course_id' => $proposal->course_id,
                    'lesson_id' => $proposal->lesson_id,
                    'status' => $proposal->status,
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
            'proposal_id' => $schema->integer()->description('Content Proposal LMS Admin already asked')->required(),
            'body_text' => $schema->string()->description('Proposed Lesson body')->required(),
            'reason' => $schema->string()->description('Why this change'),
        ];
    }
}
