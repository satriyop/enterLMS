<?php

namespace App\Domain\Xapi\Listeners;

use App\Domain\Enrollment\Events\CourseStarted;
use App\Domain\Xapi\DTOs\XapiStatementData;
use App\Domain\Xapi\Services\XapiStatementService;

class RecordXapiOnCourseStarted
{
    public function __construct(
        protected XapiStatementService $statementService,
    ) {}

    public function handle(CourseStarted $event): void
    {
        $this->statementService->record(new XapiStatementData(
            verbId: XapiStatementService::VERB_LAUNCHED,
            verbDisplay: 'launched',
            objectId: $this->statementService->buildActivityId('course', $event->enrollment->course_id),
            objectName: $event->enrollment->course->title ?? null,
            actorId: $event->enrollment->user_id,
            contextCourseId: $event->enrollment->course_id,
            contextEnrollmentId: $event->enrollment->id,
        ));
    }
}
