<?php

namespace App\Mcp\Tools\Tutor;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Tutor\Services\TutorFocusService;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Mcp\Concerns\RefusesMismatchedChannelIdentity;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\TutorFocus;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('set-focus')]
#[Description('Set messaging Focus to one enrolled, unlocked Lesson. Mentioning another Lesson does not move Focus; this call does.')]
class SetFocusTool extends Tool
{
    use AuditsAgentToolCalls;
    use RefusesMismatchedChannelIdentity;

    public function __construct(
        protected TutorFocusService $focuses,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::TUTOR_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'skin' => ['required', 'string', Rule::in(TutorFocus::skins())],
                'course_id' => ['required', 'integer', 'exists:courses,id'],
                'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
                ...$this->channelIdentityRules(),
            ], [
                'user_id.required' => 'user_id wajib diisi.',
                'skin.required' => 'skin wajib diisi.',
                'skin.in' => 'skin harus whatsapp atau telegram.',
                'course_id.required' => 'course_id wajib diisi.',
                'lesson_id.required' => 'lesson_id wajib diisi.',
                ...$this->channelIdentityMessages(),
            ]);

            if ($mismatch = $this->refuseMismatchedChannel($validated)) {
                return $mismatch;
            }

            $learner = User::query()->findOrFail($validated['user_id']);
            $course = Course::query()->findOrFail($validated['course_id']);
            $lesson = Lesson::query()->with('section')->findOrFail($validated['lesson_id']);

            $result = $this->focuses->set($learner, $validated['skin'], $course, $lesson);

            if (! $result instanceof TutorFocus) {
                return Response::error($result);
            }

            $result->loadMissing('lesson');

            return Response::structured([
                'ok' => true,
                'data' => [
                    'skin' => $result->skin,
                    'enrollment_id' => $result->enrollment_id,
                    'course_id' => $course->id,
                    'lesson_id' => $result->lesson_id,
                    'title' => $result->lesson->title,
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
            'skin' => $schema->string()->description('whatsapp or telegram')->required(),
            'course_id' => $schema->integer()->description('Course that owns the Lesson')->required(),
            'lesson_id' => $schema->integer()->description('Lesson to Focus')->required(),
        ];
    }
}
