<?php

namespace App\Mcp\Tools\Author;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\Course;
use App\Models\Lesson;
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

#[Name('get-author-lesson')]
#[Description('Read one Lesson body for the Author Agent. LMS Admin token with author.read. Does not teach Learners.')]
#[IsReadOnly]
#[IsIdempotent]
class GetAuthorLessonTool extends Tool
{
    use AuditsAgentToolCalls;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::AUTHOR_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            $user = $request->user();

            if (! $user instanceof User || ! $user->isLmsAdmin()) {
                return Response::error('Hanya LMS Admin yang boleh membaca Lesson sebagai Author Agent.');
            }

            $validated = $request->validate([
                'course_id' => ['required', 'integer', 'exists:courses,id'],
                'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            ], [
                'course_id.required' => 'course_id wajib diisi.',
                'lesson_id.required' => 'lesson_id wajib diisi.',
            ]);

            $course = Course::query()->findOrFail($validated['course_id']);
            $lesson = Lesson::query()->with(['section', 'media'])->findOrFail($validated['lesson_id']);

            if ($lesson->section->course_id !== $course->id) {
                return Response::error('Pelajaran tidak termasuk dalam Course ini.');
            }

            return Response::structured([
                'ok' => true,
                'data' => [
                    'course_id' => $course->id,
                    'lesson_id' => $lesson->id,
                    'title' => $lesson->title,
                    'content_type' => $lesson->content_type,
                    'body_text' => $lesson->readableBody(),
                    'body_ready' => $lesson->isBodyReady(),
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
            'course_id' => $schema->integer()->description('Course that owns the Lesson')->required(),
            'lesson_id' => $schema->integer()->description('Lesson to read')->required(),
        ];
    }
}
