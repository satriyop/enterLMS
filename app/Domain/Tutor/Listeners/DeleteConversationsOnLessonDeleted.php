<?php

namespace App\Domain\Tutor\Listeners;

use App\Domain\Progress\Events\LessonDeleted;
use App\Domain\Tutor\Services\ConversationService;

class DeleteConversationsOnLessonDeleted
{
    public function __construct(
        protected ConversationService $conversations,
    ) {}

    public function handle(LessonDeleted $event): void
    {
        $this->conversations->deleteForLesson($event->lessonId);
    }
}
