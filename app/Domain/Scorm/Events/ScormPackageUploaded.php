<?php

namespace App\Domain\Scorm\Events;

use App\Domain\Shared\Contracts\DomainEvent;
use App\Models\ScormPackage;

class ScormPackageUploaded extends DomainEvent
{
    public function __construct(
        public readonly ScormPackage $package,
        ?int $actorId = null
    ) {
        parent::__construct($actorId);
    }

    public function getEventName(): string
    {
        return 'scorm.package_uploaded';
    }

    public function getMetadata(): array
    {
        return [
            'package_id' => $this->package->id,
            'title' => $this->package->title,
            'version' => $this->package->version,
            'original_filename' => $this->package->original_filename,
            'file_size_bytes' => $this->package->file_size_bytes,
        ];
    }

    public function getAggregateId(): int|string
    {
        return $this->package->id;
    }

    public function getAggregateType(): string
    {
        return 'scorm_package';
    }
}
