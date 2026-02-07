<?php

namespace App\Domain\Xapi\DTOs;

/**
 * @phpstan-type XapiStatementDataArray array{
 *     actor_id?: int|null,
 *     actor_mbox?: string|null,
 *     actor_name?: string|null,
 *     verb_id: string,
 *     verb_display: string,
 *     object_type?: string,
 *     object_id: string,
 *     object_name?: string|null,
 *     result_score_raw?: float|null,
 *     result_score_min?: float|null,
 *     result_score_max?: float|null,
 *     result_score_scaled?: float|null,
 *     result_success?: bool|null,
 *     result_completion?: bool|null,
 *     result_duration?: string|null,
 *     context_registration?: string|null,
 *     context_course_id?: int|null,
 *     context_enrollment_id?: int|null,
 *     context_extensions?: array<string, mixed>|null,
 *     source?: string
 * }
 */
final readonly class XapiStatementData
{
    /**
     * @param  array<string, mixed>|null  $contextExtensions
     */
    public function __construct(
        public string $verbId,
        public string $verbDisplay,
        public string $objectId,
        public ?string $objectName = null,
        public string $objectType = 'Activity',
        public ?int $actorId = null,
        public ?string $actorMbox = null,
        public ?string $actorName = null,
        public ?float $resultScoreRaw = null,
        public ?float $resultScoreMin = null,
        public ?float $resultScoreMax = null,
        public ?float $resultScoreScaled = null,
        public ?bool $resultSuccess = null,
        public ?bool $resultCompletion = null,
        public ?string $resultDuration = null,
        public ?string $contextRegistration = null,
        public ?int $contextCourseId = null,
        public ?int $contextEnrollmentId = null,
        public ?array $contextExtensions = null,
        public string $source = 'native',
    ) {}

    /**
     * @param  XapiStatementDataArray  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            verbId: $data['verb_id'],
            verbDisplay: $data['verb_display'],
            objectId: $data['object_id'],
            objectName: $data['object_name'] ?? null,
            objectType: $data['object_type'] ?? 'Activity',
            actorId: $data['actor_id'] ?? null,
            actorMbox: $data['actor_mbox'] ?? null,
            actorName: $data['actor_name'] ?? null,
            resultScoreRaw: $data['result_score_raw'] ?? null,
            resultScoreMin: $data['result_score_min'] ?? null,
            resultScoreMax: $data['result_score_max'] ?? null,
            resultScoreScaled: $data['result_score_scaled'] ?? null,
            resultSuccess: $data['result_success'] ?? null,
            resultCompletion: $data['result_completion'] ?? null,
            resultDuration: $data['result_duration'] ?? null,
            contextRegistration: $data['context_registration'] ?? null,
            contextCourseId: $data['context_course_id'] ?? null,
            contextEnrollmentId: $data['context_enrollment_id'] ?? null,
            contextExtensions: $data['context_extensions'] ?? null,
            source: $data['source'] ?? 'native',
        );
    }
}
