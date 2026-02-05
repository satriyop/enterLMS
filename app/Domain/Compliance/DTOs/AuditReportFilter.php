<?php

namespace App\Domain\Compliance\DTOs;

use Carbon\Carbon;

final readonly class AuditReportFilter
{
    /**
     * @param  array<string>|null  $eventTypes  Filter by event names
     * @param  array<string>|null  $aggregateTypes  Filter by aggregate types (Course, Enrollment, etc.)
     */
    public function __construct(
        public Carbon $startDate,
        public Carbon $endDate,
        public ?array $eventTypes = null,
        public ?array $aggregateTypes = null,
        public ?int $userId = null,
        public ?int $courseId = null,
    ) {}

    /**
     * @param  array{
     *     start_date: string,
     *     end_date: string,
     *     event_types?: array<string>|null,
     *     aggregate_types?: array<string>|null,
     *     user_id?: int|null,
     *     course_id?: int|null
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            startDate: Carbon::parse($data['start_date'])->startOfDay(),
            endDate: Carbon::parse($data['end_date'])->endOfDay(),
            eventTypes: $data['event_types'] ?? null,
            aggregateTypes: $data['aggregate_types'] ?? null,
            userId: $data['user_id'] ?? null,
            courseId: $data['course_id'] ?? null,
        );
    }
}
