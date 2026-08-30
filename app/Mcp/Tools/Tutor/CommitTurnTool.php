<?php

namespace App\Mcp\Tools\Tutor;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Tutor\Services\ConversationService;
use App\Domain\Tutor\Services\TutorAccess;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Mcp\Concerns\RefusesMismatchedChannelIdentity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

#[Name('commit-turn')]
#[Description('Record the Learner turn then the Tutor turn on that Enrollment + Lesson Conversation. Do not send a messaging reply unless this succeeds.')]
class CommitTurnTool extends Tool
{
    use AuditsAgentToolCalls;
    use RefusesMismatchedChannelIdentity;

    public function __construct(
        protected TutorAccess $access,
        protected ConversationService $conversations,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::TUTOR_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'course_id' => ['required', 'integer', 'exists:courses,id'],
                'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
                'learner_message' => ['required', 'string', 'min:1', 'max:4000'],
                'tutor_message' => ['required', 'string', 'min:1', 'max:8000'],
                ...$this->channelIdentityRules(),
            ], [
                'user_id.required' => 'user_id wajib diisi.',
                'course_id.required' => 'course_id wajib diisi.',
                'lesson_id.required' => 'lesson_id wajib diisi.',
                'learner_message.required' => 'Pesan Learner wajib diisi.',
                'tutor_message.required' => 'Pesan Tutor wajib diisi.',
                ...$this->channelIdentityMessages(),
            ]);

            if ($mismatch = $this->refuseMismatchedChannel($validated)) {
                return $mismatch;
            }

            $course = Course::query()->findOrFail($validated['course_id']);

            if (! $course->isPublished()) {
                return Response::error('Kursus belum dipublikasikan.');
            }

            $lesson = Lesson::query()->with('section')->findOrFail($validated['lesson_id']);
            $enrollment = $this->access->enrollmentForLesson($validated['user_id'], $course, $lesson);

            if (! $enrollment instanceof Enrollment) {
                return Response::error($enrollment);
            }

            try {
                $conversation = $this->conversations->persistTurns(
                    $enrollment,
                    $lesson,
                    $validated['learner_message'],
                    $validated['tutor_message'],
                );
            } catch (RuntimeException $e) {
                return Response::error($e->getMessage());
            }

            return Response::structured([
                'ok' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'enrollment_id' => $enrollment->id,
                    'lesson_id' => $lesson->id,
                    'turns' => $conversation->turns->count(),
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
            'user_id' => $schema->integer()->description('Named Learner')->required(),
            'course_id' => $schema->integer()->description('Course that owns the Lesson')->required(),
            'lesson_id' => $schema->integer()->description('Focus Lesson to write')->required(),
            'learner_message' => $schema->string()->description('Learner turn body')->required(),
            'tutor_message' => $schema->string()->description('Tutor turn body')->required(),
        ];
    }
}
