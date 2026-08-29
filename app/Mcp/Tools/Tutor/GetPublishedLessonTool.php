<?php

namespace App\Mcp\Tools\Tutor;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\TipTapRenderer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-published-lesson')]
#[Description('Get the current published body of one Lesson in a published Course. Scoped to course_id; does not return other Courses or later Lesson bodies.')]
#[IsReadOnly]
#[IsIdempotent]
class GetPublishedLessonTool extends Tool
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
                'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            ], [
                'course_id.required' => 'course_id wajib diisi.',
                'lesson_id.required' => 'lesson_id wajib diisi.',
            ]);

            $course = Course::query()->findOrFail($validated['course_id']);

            if (! $course->isPublished()) {
                return Response::error('Kursus belum dipublikasikan.');
            }

            $lesson = Lesson::query()
                ->with('section')
                ->findOrFail($validated['lesson_id']);

            if ($lesson->section->course_id !== $course->id) {
                return Response::error('Pelajaran tidak termasuk dalam Course ini.');
            }

            $bodyText = $this->lessonText($lesson);

            return Response::structured([
                'ok' => true,
                'data' => [
                    'course_id' => $course->id,
                    'lesson_id' => $lesson->id,
                    'title' => $lesson->title,
                    'description' => $lesson->description,
                    'content_type' => $lesson->content_type,
                    'body_text' => $bodyText,
                    'body_html' => (new TipTapRenderer)->render($lesson->rich_content),
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
            'course_id' => $schema->integer()->description('Course ID that must own the Lesson')->required(),
            'lesson_id' => $schema->integer()->description('Lesson ID to read')->required(),
        ];
    }

    private function lessonText(Lesson $lesson): string
    {
        $parts = array_filter([
            $lesson->title,
            $lesson->description,
            is_array($lesson->rich_content) ? $this->flattenRichContent($lesson->rich_content) : null,
        ]);

        return trim(implode(' ', $parts));
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function flattenRichContent(array $node): string
    {
        $text = '';

        if (isset($node['text']) && is_string($node['text'])) {
            $text .= $node['text'].' ';
        }

        if (isset($node['content']) && is_array($node['content'])) {
            foreach ($node['content'] as $child) {
                if (is_array($child)) {
                    $text .= $this->flattenRichContent($child);
                }
            }
        }

        return $text;
    }
}
