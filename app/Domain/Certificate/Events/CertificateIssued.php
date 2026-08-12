<?php

namespace App\Domain\Certificate\Events;

use App\Domain\Shared\Contracts\DomainEvent;
use App\Models\Certificate;

class CertificateIssued extends DomainEvent
{
    public function __construct(
        public readonly Certificate $certificate,
        ?int $actorId = null,
    ) {
        parent::__construct($actorId);
    }

    public function getEventName(): string
    {
        return 'certificate.issued';
    }

    public function getMetadata(): array
    {
        return [
            'certificate_id' => $this->certificate->id,
            'certificate_number' => $this->certificate->certificate_number,
            'user_id' => $this->certificate->user_id,
            'enrollment_id' => $this->certificate->enrollment_id,
            'certificable_type' => $this->certificate->certificable_type,
            'certificable_id' => $this->certificate->certificable_id,
            'certificable_title' => $this->certificate->certificable_title,
            'verification_code' => $this->certificate->verification_code,
            'issued_at' => $this->certificate->issued_at?->toIso8601String(),
        ];
    }

    public function getAggregateId(): int|string
    {
        return $this->certificate->id;
    }

    public function getAggregateType(): string
    {
        return 'certificate';
    }
}
