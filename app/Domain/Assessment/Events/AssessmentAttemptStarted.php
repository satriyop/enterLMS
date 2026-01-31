<?php

namespace App\Domain\Assessment\Events;

use App\Domain\Shared\Contracts\DomainEvent;
use App\Models\AssessmentAttempt;

class AssessmentAttemptStarted extends DomainEvent
{
    public function __construct(
        public readonly AssessmentAttempt $attempt,
        ?int $actorId = null
    ) {
        parent::__construct($actorId);
    }

    public function getEventName(): string
    {
        return 'assessment.attempt.started';
    }

    public function getMetadata(): array
    {
        return [
            'attempt_id' => $this->attempt->id,
            'assessment_id' => $this->attempt->assessment_id,
            'user_id' => $this->attempt->user_id,
            'attempt_number' => $this->attempt->attempt_number,
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
