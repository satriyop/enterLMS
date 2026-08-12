<?php

namespace App\Mcp\Tools\Agent;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\Enrollment;
use App\Models\LessonProgress;
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

#[Name('get-progress')]
#[Description('List lesson progress rows for an enrollment owned by the token user.')]
#[IsReadOnly]
#[IsIdempotent]
class GetProgressTool extends Tool
{
    use AuditsAgentToolCalls;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::PROGRESS_READ)) {
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

            $enrollment = Enrollment::query()->findOrFail($validated['enrollment_id']);

            if ($enrollment->user_id !== $user->id) {
                return Response::error('Enrollment tidak milik pengguna token (acting-as).');
            }

            $rows = LessonProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->with(['lesson:id,title,content_type,order,course_section_id'])
                ->get()
                ->map(fn (LessonProgress $p) => [
                    'lesson_id' => $p->lesson_id,
                    'lesson_title' => $p->lesson?->title,
                    'is_completed' => (bool) $p->is_completed,
                    'completed_at' => $p->completed_at?->toIso8601String(),
                    'current_page' => $p->current_page,
                    'media_position_seconds' => $p->media_position_seconds,
                    'last_viewed_at' => $p->last_viewed_at?->toIso8601String(),
                ])->values()->all();

            return Response::structured([
                'ok' => true,
                'data' => [
                    'enrollment_id' => $enrollment->id,
                    'progress_percentage' => $enrollment->progress_percentage,
                    'status' => $enrollment->status->getValue(),
                    'lessons' => $rows,
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
