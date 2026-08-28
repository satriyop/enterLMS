<?php

namespace App\Domain\Tutor\Listeners;

use App\Domain\Enrollment\Events\UserReenrolled;
use App\Domain\Tutor\Services\ConversationService;

class ResetConversationsOnFreshReenroll
{
    public function __construct(
        protected ConversationService $conversations,
    ) {}

    public function handle(UserReenrolled $event): void
    {
        if ($event->progressPreserved) {
            return;
        }

        $this->conversations->resetForEnrollment($event->enrollment);
    }
}
