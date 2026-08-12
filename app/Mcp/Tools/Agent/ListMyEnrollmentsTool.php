<?php

namespace App\Mcp\Tools\Agent;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\Enrollment;
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

#[Name('list-my-enrollments')]
#[Description('List enrollments for the authenticated token owner (acting-as user).')]
#[IsReadOnly]
#[IsIdempotent]
class ListMyEnrollmentsTool extends Tool
{
    use AuditsAgentToolCalls;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::ENROLLMENT_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            /** @var User $user */
            $user = $request->user();

            $validated = $request->validate([
                'status' => ['nullable', 'string', 'in:active,completed,dropped'],
            ]);

            $query = Enrollment::query()
                ->where('user_id', $user->id)
                ->with(['course:id,title,slug,status,visibility'])
                ->orderByDesc('enrolled_at');

            if (! empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            $rows = $query->get()->map(fn (Enrollment $e) => [
                'id' => $e->id,
                'status' => $e->status->getValue(),
                'progress_percentage' => $e->progress_percentage,
                'enrolled_at' => $e->enrolled_at?->toIso8601String(),
                'completed_at' => $e->completed_at?->toIso8601String(),
                'course' => $e->course ? [
                    'id' => $e->course->id,
                    'title' => $e->course->title,
                    'slug' => $e->course->slug,
                ] : null,
            ])->values()->all();

            return Response::structured([
                'ok' => true,
                'data' => $rows,
            ]);
        });
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Optional filter: active|completed|dropped'),
        ];
    }
}
