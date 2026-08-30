<?php

namespace App\Domain\Tutor\Services;

use App\Models\Conversation;
use App\Models\ConversationTurn;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            Log::warning('Tutor runtime failed.', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Tutor runtime failed.', previous: $e);
        }

        return $this->persistTurns($enrollment, $lesson, $message, $reply);
    }

    /**
     * Overlay and MCP both write Learner then Tutor turns here. Persist both or neither.
     */
    public function persistTurns(Enrollment $enrollment, Lesson $lesson, string $learnerMessage, string $tutorReply): Conversation
    {
        if (! $enrollment->isActive() && ! $enrollment->isCompleted()) {
            throw new RuntimeException('Enrollment tidak memungkinkan percakapan baru.');
        }

        $lesson->loadMissing('section');

        if ($lesson->section->course_id !== $enrollment->course_id) {
            throw new RuntimeException('Pelajaran tidak termasuk dalam Course ini.');
        }

        try {
            return DB::transaction(function () use ($enrollment, $lesson, $learnerMessage, $tutorReply) {
                $conversation = Conversation::query()->firstOrCreate([
                    'enrollment_id' => $enrollment->id,
                    'lesson_id' => $lesson->id,
                ]);

                $conversation->turns()->create([
                    'role' => ConversationTurn::ROLE_LEARNER,
                    'body' => $learnerMessage,
                ]);
                $conversation->turns()->create([
                    'role' => ConversationTurn::ROLE_TUTOR,
                    'body' => $tutorReply,
                ]);

                return $conversation->fresh(['turns']);
            });
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal menyimpan percakapan.', previous: $e);
        }
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
