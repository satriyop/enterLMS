<?php

namespace App\Domain\Xapi\Listeners;

use App\Domain\Scorm\Events\ScormLessonCompleted;
use App\Domain\Xapi\DTOs\XapiStatementData;
use App\Domain\Xapi\Services\XapiStatementService;

class RecordXapiOnScormCompleted
{
    public function __construct(
        protected XapiStatementService $statementService,
    ) {}

    public function handle(ScormLessonCompleted $event): void
    {
        $tracking = $event->tracking;

        $this->statementService->record(new XapiStatementData(
            verbId: XapiStatementService::VERB_COMPLETED,
            verbDisplay: 'completed',
            objectId: $this->statementService->buildActivityId('lesson', $event->lesson->id),
            objectName: $event->lesson->title,
            actorId: $event->enrollment->user_id,
            resultScoreRaw: $tracking->score_raw !== null ? (float) $tracking->score_raw : null,
            resultScoreMin: $tracking->score_min !== null ? (float) $tracking->score_min : null,
            resultScoreMax: $tracking->score_max !== null ? (float) $tracking->score_max : null,
            resultScoreScaled: $tracking->score_scaled !== null ? (float) $tracking->score_scaled : null,
            resultCompletion: true,
            resultSuccess: $tracking->is_passed,
            contextCourseId: $event->enrollment->course_id,
            contextEnrollmentId: $event->enrollment->id,
            source: 'scorm',
        ));
    }
}
