<?php

namespace App\Domain\Assessment\Events;

use App\Domain\Shared\Contracts\DomainEvent;
use App\Models\AssessmentAttempt;

class AssessmentGraded extends DomainEvent
{
    public function __construct(
        public readonly AssessmentAttempt $attempt,
        ?int $actorId = null
    ) {
        parent::__construct($actorId);
    }

    public function getEventName(): string
    {
        return 'assessment.graded';
    }

    public function getMetadata(): array
    {
        return [
            'attempt_id' => $this->attempt->id,
            'assessment_id' => $this->attempt->assessment_id,
            'user_id' => $this->attempt->user_id,
            'score' => $this->attempt->score,
            'total_points' => $this->attempt->total_points,
            'passed' => $this->attempt->passed,
            'graded_by' => $this->actorId,
        ];
    }

    public function getAggregateId(): int|string
    {
        return $this->attempt->id;
    }

    public function getAggregateType(): string
    {
        return 'assessment_attempt';
    }
}
