<?php

namespace App\Domain\Scorm\DTOs;

/**
 * @phpstan-type ManifestDataArray array{
 *     version: string,
 *     identifier: string|null,
 *     title: string,
 *     entry_point: string,
 *     schema_version: string|null,
 *     metadata: array<string, mixed>
 * }
 */
final readonly class ManifestData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $version,
        public ?string $identifier,
        public string $title,
        public string $entryPoint,
        public ?string $schemaVersion,
        public array $metadata = [],
    ) {}

    /**
     * @param  ManifestDataArray  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            version: $data['version'],
            identifier: $data['identifier'] ?? null,
            title: $data['title'],
            entryPoint: $data['entry_point'],
            schemaVersion: $data['schema_version'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * @return ManifestDataArray
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'identifier' => $this->identifier,
            'title' => $this->title,
            'entry_point' => $this->entryPoint,
            'schema_version' => $this->schemaVersion,
            'metadata' => $this->metadata,
        ];
    }
}
