<?php

namespace App\Domain\Xapi\Events;

use App\Domain\Shared\Contracts\DomainEvent;
use App\Models\XapiStatement;

class XapiStatementRecorded extends DomainEvent
{
    public function __construct(
        public readonly XapiStatement $statement,
        ?int $actorId = null
    ) {
        parent::__construct($actorId);
    }

    public function getEventName(): string
    {
        return 'xapi.statement_recorded';
    }

    public function getMetadata(): array
    {
        return [
            'statement_id' => $this->statement->statement_id,
            'verb' => $this->statement->verb_display,
            'object_id' => $this->statement->object_id,
            'source' => $this->statement->source,
        ];
    }

    public function getAggregateId(): int|string
    {
        return $this->statement->statement_id;
    }

    public function getAggregateType(): string
    {
        return 'xapi_statement';
    }
}
