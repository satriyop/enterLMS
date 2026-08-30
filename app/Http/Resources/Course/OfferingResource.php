<?php

namespace App\Http\Resources\Course;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Offering
 */
class OfferingResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     id: int,
     *     course_id: int,
     *     name: string,
     *     code: string,
     *     starts_at: string|null,
     *     ends_at: string|null,
     *     capacity: int|null,
     *     is_default: bool,
     *     is_open: bool,
     *     enrollments_count: int|null,
     *     facilitator: array{id: int, name: string}|null
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'name' => $this->name,
            'code' => $this->code,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'capacity' => $this->capacity,
            'is_default' => $this->is_default,
            'is_open' => $this->isOpenForEnrollment(),
            'enrollments_count' => $this->whenCounted('enrollments'),
            'facilitator' => $this->whenLoaded('facilitator', fn () => $this->facilitator === null
                ? null
                : [
                    'id' => $this->facilitator->id,
                    'name' => $this->facilitator->name,
                    'email' => $this->facilitator->email,
                ]),
        ];
    }
}
