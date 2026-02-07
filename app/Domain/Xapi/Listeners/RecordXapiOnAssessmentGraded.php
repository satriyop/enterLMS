<?php

namespace App\Domain\Xapi\Listeners;

use App\Domain\Assessment\Events\AssessmentGraded;
use App\Domain\Xapi\DTOs\XapiStatementData;
use App\Domain\Xapi\Services\XapiStatementService;

class RecordXapiOnAssessmentGraded
{
    public function __construct(
        protected XapiStatementService $statementService,
    ) {}

    public function handle(AssessmentGraded $event): void
    {
        $attempt = $event->attempt;
        $assessment = $attempt->assessment;

        $passed = $attempt->score >= ($assessment->passing_score ?? 0);

        $this->statementService->record(new XapiStatementData(
            verbId: $passed ? XapiStatementService::VERB_PASSED : XapiStatementService::VERB_FAILED,
            verbDisplay: $passed ? 'passed' : 'failed',
            objectId: $this->statementService->buildActivityId('assessment', $assessment->id),
            objectName: $assessment->title,
            actorId: $attempt->user_id,
            resultScoreRaw: (float) $attempt->score,
            resultScoreMin: 0,
            resultScoreMax: (float) ($assessment->total_points ?? 100),
            resultScoreScaled: $assessment->total_points > 0
                ? round($attempt->score / $assessment->total_points, 4)
                : null,
            resultSuccess: $passed,
            contextCourseId: $assessment->course_id,
        ));
    }
}
