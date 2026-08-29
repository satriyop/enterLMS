<?php

namespace App\Mcp\Tools\Tutor;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\Course;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-course-outline')]
#[Description('Get section and Lesson titles for a published Course. Titles only — no Lesson bodies.')]
#[IsReadOnly]
#[IsIdempotent]
class GetCourseOutlineTool extends Tool
{
    use AuditsAgentToolCalls;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::TUTOR_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            $validated = $request->validate([
                'course_id' => ['required', 'integer', 'exists:courses,id'],
            ], [
                'course_id.required' => 'course_id wajib diisi.',
            ]);

            $course = Course::query()
                ->with([
                    'sections' => fn ($q) => $q->orderBy('order'),
                    'sections.lessons' => fn ($q) => $q->orderBy('order'),
                ])
                ->findOrFail($validated['course_id']);

            if (! $course->isPublished()) {
                return Response::error('Kursus belum dipublikasikan.');
            }

            return Response::structured([
                'ok' => true,
                'data' => [
                    'course_id' => $course->id,
                    'title' => $course->title,
                    'sections' => $course->sections->map(fn ($section) => [
                        'id' => $section->id,
                        'title' => $section->title,
                        'order' => $section->order,
                        'lessons' => $section->lessons->map(fn ($lesson) => [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                            'order' => $lesson->order,
                        ])->values()->all(),
                    ])->values()->all(),
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
            'course_id' => $schema->integer()->description('Course ID')->required(),
        ];
    }
}
