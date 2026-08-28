<?php

namespace App\Domain\Tutor\Services;

use App\Models\Conversation;
use App\Models\ConversationTurn;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ConversationService
{
    public function __construct(
        protected TutorRuntime $runtime,
    ) {}

    public function forEnrollmentAndLesson(Enrollment $enrollment, Lesson $lesson): ?Conversation
    {
        return Conversation::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('lesson_id', $lesson->id)
            ->with(['turns', 'enrollment'])
            ->first();
    }

    public function postTurn(Enrollment $enrollment, Lesson $lesson, string $message): Conversation
    {
        $conversation = Conversation::query()->firstOrCreate([
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $lesson->id,
        ]);

        $conversation->load(['lesson.section.course', 'turns']);

        try {
            $reply = $this->runtime->completeTurn($conversation, $message);
        } catch (Throwable $e) {
            throw new RuntimeException('Tutor runtime failed.', previous: $e);
        }

        return DB::transaction(function () use ($conversation, $message, $reply) {
            $conversation->turns()->create([
                'role' => ConversationTurn::ROLE_LEARNER,
                'body' => $message,
            ]);
            $conversation->turns()->create([
                'role' => ConversationTurn::ROLE_TUTOR,
                'body' => $reply,
            ]);

            return $conversation->fresh(['turns']);
        });
    }

    public function resetForEnrollment(Enrollment $enrollment): void
    {
        Conversation::query()
            ->where('enrollment_id', $enrollment->id)
            ->each(function (Conversation $conversation) {
                $conversation->turns()->delete();
                $conversation->delete();
            });
    }

    public function deleteForLesson(int $lessonId): void
    {
        Conversation::query()
            ->where('lesson_id', $lessonId)
            ->each(function (Conversation $conversation) {
                $conversation->turns()->delete();
                $conversation->delete();
            });
    }
}
