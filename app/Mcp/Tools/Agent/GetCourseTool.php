<?php

namespace App\Mcp\Tools\Agent;

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

#[Name('get-course')]
#[Description('Get a published public course with sections and lessons summary.')]
#[IsReadOnly]
#[IsIdempotent]
class GetCourseTool extends Tool
{
    use AuditsAgentToolCalls;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::COURSE_READ)) {
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
                    'category:id,name,slug',
                    'sections' => fn ($q) => $q->orderBy('order'),
                    'sections.lessons' => fn ($q) => $q->orderBy('order'),
                ])
                ->findOrFail($validated['course_id']);

            if (! $course->isPublished() || $course->visibility !== 'public') {
                return Response::error('Kursus tidak tersedia di katalog publik.');
            }

            return Response::structured([
                'ok' => true,
                'data' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'short_description' => $course->short_description,
                    'category' => $course->category?->name,
                    'difficulty_level' => $course->difficulty_level,
                    'requires_payment' => $course->isPaid(),
                    'is_paid_flag' => (bool) $course->is_paid,
                    'sections' => $course->sections->map(fn ($section) => [
                        'id' => $section->id,
                        'title' => $section->title,
                        'order' => $section->order,
                        'lessons' => $section->lessons->map(fn ($lesson) => [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                            'order' => $lesson->order,
                            'content_type' => $lesson->content_type,
                            'estimated_duration_minutes' => $lesson->estimated_duration_minutes,
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
