<?php

namespace App\Mcp\Tools\Agent;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Compliance\DTOs\AuditReportFilter;
use App\Domain\Compliance\Services\AuditReportService;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-audit-events')]
#[Description('List domain audit events for compliance (role-gated). Filters: date range, event name, actor, course.')]
#[IsReadOnly]
#[IsIdempotent]
class ListAuditEventsTool extends Tool
{
    use AuditsAgentToolCalls;

    public function __construct(
        protected AuditReportService $auditReportService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireComplianceAccess($request, AgentAbility::COMPLIANCE_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            $validated = $request->validate([
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'event_name' => ['nullable', 'string', 'max:100'],
                'actor_id' => ['nullable', 'integer', 'exists:users,id'],
                'course_id' => ['nullable', 'integer', 'exists:courses,id'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            ]);

            $filter = AuditReportFilter::fromArray([
                'start_date' => $validated['start_date'] ?? now()->subDays(30)->toDateString(),
                'end_date' => $validated['end_date'] ?? now()->toDateString(),
                'event_types' => isset($validated['event_name']) ? [$validated['event_name']] : null,
                'user_id' => $validated['actor_id'] ?? null,
                'course_id' => $validated['course_id'] ?? null,
            ]);

            $limit = (int) ($validated['limit'] ?? 50);

            $rows = $this->auditReportService->getAuditLog($filter)
                ->take($limit)
                ->map(function ($log) {
                    $metadata = is_string($log->metadata ?? null)
                        ? json_decode($log->metadata, true)
                        : ($log->metadata ?? null);

                    return [
                        'id' => $log->id,
                        'event_id' => $log->event_id,
                        'event_name' => $log->event_name,
                        'aggregate_type' => $log->aggregate_type,
                        'aggregate_id' => $log->aggregate_id,
                        'actor_id' => $log->actor_id,
                        'actor_name' => $log->actor_name,
                        'metadata' => $this->sanitizeMetadata(is_array($metadata) ? $metadata : []),
                        'occurred_at' => $log->occurred_at,
                    ];
                })
                ->values()
                ->all();

            return Response::structured([
                'ok' => true,
                'data' => $rows,
                'meta' => [
                    'count' => count($rows),
                    'start_date' => $filter->startDate->toDateString(),
                    'end_date' => $filter->endDate->toDateString(),
                    'limit' => $limit,
                ],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        foreach (['password', 'token', 'secret', 'authorization', 'api_key', 'remember_token'] as $key) {
            unset($metadata[$key]);
        }

        return $metadata;
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('Start date YYYY-MM-DD (default: 30 days ago)'),
            'end_date' => $schema->string()->description('End date YYYY-MM-DD (default: today)'),
            'event_name' => $schema->string()->description('Exact domain event name filter'),
            'actor_id' => $schema->integer()->description('Filter by actor user id'),
            'course_id' => $schema->integer()->description('Filter by course id in aggregate/metadata'),
            'limit' => $schema->integer()->description('Max rows (default 50, max 200)'),
        ];
    }
}
