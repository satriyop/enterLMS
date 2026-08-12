<?php

namespace App\Mcp\Tools\Agent;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Progress\Services\ProgressTrackingService;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('mark-lesson-complete')]
#[Description('Mark a lesson complete for an enrollment owned by the token user (active enrollment required).')]
#[IsIdempotent]
class MarkLessonCompleteTool extends Tool
{
    use AuditsAgentToolCalls;

    public function __construct(
        protected ProgressTrackingService $progressService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::PROGRESS_WRITE)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            /** @var User $user */
            $user = $request->user();

            $validated = $request->validate([
                'enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
                'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            ], [
                'enrollment_id.required' => 'enrollment_id wajib diisi.',
                'lesson_id.required' => 'lesson_id wajib diisi.',
            ]);

            $enrollment = Enrollment::query()->findOrFail($validated['enrollment_id']);

            if ($enrollment->user_id !== $user->id) {
                return Response::error('Enrollment tidak milik pengguna token. code=forbidden');
            }

            if (! $enrollment->isActive()) {
                return Response::error('Enrollment harus aktif untuk menandai progress. code=not_active');
            }

            $lesson = Lesson::query()->findOrFail($validated['lesson_id']);

            $belongs = DB::table('lessons')
                ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
                ->where('lessons.id', $lesson->id)
                ->where('course_sections.course_id', $enrollment->course_id)
                ->exists();

            if (! $belongs) {
                return Response::error('Lesson tidak termasuk kursus enrollment ini. code=lesson_mismatch');
            }

            $result = $this->progressService->completeLesson($enrollment, $lesson);

            return Response::structured([
                'ok' => true,
                'data' => [
                    'enrollment_id' => $enrollment->id,
                    'lesson_id' => $lesson->id,
                    'lesson_completed' => $result->lessonCompleted,
                    'course_completed' => $result->courseCompleted,
                    'progress_percentage' => $result->coursePercentage->value,
                    'status' => $result->courseCompleted ? 'completed' : 'active',
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
            'lesson_id' => $schema->integer()->description('Lesson ID')->required(),
        ];
    }
}
