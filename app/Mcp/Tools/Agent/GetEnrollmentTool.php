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

#[Name('get-enrollment')]
#[Description('Get one enrollment owned by the token user, including progress percentage.')]
#[IsReadOnly]
#[IsIdempotent]
class GetEnrollmentTool extends Tool
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
                'enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            ], [
                'enrollment_id.required' => 'enrollment_id wajib diisi.',
            ]);

            $enrollment = Enrollment::query()
                ->with(['course:id,title,slug'])
                ->findOrFail($validated['enrollment_id']);

            if ($enrollment->user_id !== $user->id) {
                return Response::error('Enrollment tidak milik pengguna token (acting-as).');
            }

            return Response::structured([
                'ok' => true,
                'data' => [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status->getValue(),
                    'progress_percentage' => $enrollment->progress_percentage,
                    'enrolled_at' => $enrollment->enrolled_at?->toIso8601String(),
                    'completed_at' => $enrollment->completed_at?->toIso8601String(),
                    'last_lesson_id' => $enrollment->last_lesson_id,
                    'course' => $enrollment->course ? [
                        'id' => $enrollment->course->id,
                        'title' => $enrollment->course->title,
                        'slug' => $enrollment->course->slug,
                    ] : null,
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
            'enrollment_id' => $schema->integer()->description('Enrollment ID')->required(),
        ];
    }
}
