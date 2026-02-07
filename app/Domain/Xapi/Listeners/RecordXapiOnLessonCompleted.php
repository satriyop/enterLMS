<?php

namespace App\Domain\Xapi\Listeners;

use App\Domain\Progress\Events\LessonCompleted;
use App\Domain\Xapi\DTOs\XapiStatementData;
use App\Domain\Xapi\Services\XapiStatementService;

class RecordXapiOnLessonCompleted
{
    public function __construct(
        protected XapiStatementService $statementService,
    ) {}

    public function handle(LessonCompleted $event): void
    {
        $this->statementService->record(new XapiStatementData(
            verbId: XapiStatementService::VERB_COMPLETED,
            verbDisplay: 'completed',
            objectId: $this->statementService->buildActivityId('lesson', $event->lesson->id),
            objectName: $event->lesson->title,
            actorId: $event->enrollment->user_id,
            resultCompletion: true,
            contextCourseId: $event->enrollment->course_id,
            contextEnrollmentId: $event->enrollment->id,
        ));
    }
}
