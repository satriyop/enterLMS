<?php

namespace App\Domain\Xapi\Listeners;

use App\Domain\Enrollment\Events\EnrollmentCompleted;
use App\Domain\Xapi\DTOs\XapiStatementData;
use App\Domain\Xapi\Services\XapiStatementService;

class RecordXapiOnEnrollmentCompleted
{
    public function __construct(
        protected XapiStatementService $statementService,
    ) {}

    public function handle(EnrollmentCompleted $event): void
    {
        $this->statementService->record(new XapiStatementData(
            verbId: XapiStatementService::VERB_COMPLETED,
            verbDisplay: 'completed',
            objectId: $this->statementService->buildActivityId('course', $event->enrollment->course_id),
            objectName: $event->enrollment->course->title ?? null,
            actorId: $event->enrollment->user_id,
            resultCompletion: true,
            contextCourseId: $event->enrollment->course_id,
            contextEnrollmentId: $event->enrollment->id,
        ));
    }
}
