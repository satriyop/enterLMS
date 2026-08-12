<?php

namespace App\Mcp\Tools\Agent;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\Certificate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-certificates')]
#[Description('List issued certificates with optional filters (compliance role-gated).')]
#[IsReadOnly]
#[IsIdempotent]
class ListCertificatesTool extends Tool
{
    use AuditsAgentToolCalls;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireComplianceAccess($request, AgentAbility::COMPLIANCE_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            $validated = $request->validate([
                'user_id' => ['nullable', 'integer', 'exists:users,id'],
                'status' => ['nullable', 'string', 'in:active,revoked,expired'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            ]);

            $limit = (int) ($validated['limit'] ?? 50);

            $query = Certificate::query()
                ->with(['user:id,name,email'])
                ->orderByDesc('issued_at');

            if (! empty($validated['user_id'])) {
                $query->where('user_id', $validated['user_id']);
            }

            if (! empty($validated['status'])) {
                $query->where('status', $validated['status']);
            } else {
                $query->active();
            }

            $rows = $query->limit($limit)->get()->map(fn (Certificate $c) => [
                'id' => $c->id,
                'certificate_number' => $c->certificate_number,
                'type' => $c->type,
                'status' => $c->status,
                'certificable_title' => $c->certificable_title,
                'issued_at' => $c->issued_at?->toIso8601String(),
                'verification_code' => $c->verification_code,
                'user' => $c->user ? [
                    'id' => $c->user->id,
                    'name' => $c->user->name,
                    'email' => $c->user->email,
                ] : null,
            ])->values()->all();

            return Response::structured([
                'ok' => true,
                'data' => $rows,
                'meta' => [
                    'count' => count($rows),
                    'limit' => $limit,
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
            'user_id' => $schema->integer()->description('Optional filter by recipient user id'),
            'status' => $schema->string()->description('active|revoked|expired (default active)'),
            'limit' => $schema->integer()->description('Max rows (default 50)'),
        ];
    }
}
