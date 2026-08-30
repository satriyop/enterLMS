<?php

namespace App\Http\Resources\Course;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight enrollment snippet for course/lesson views.
 *
 * @mixin \App\Models\Enrollment
 */
class EnrollmentSummaryResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => (string) $this->status,
            'enrolled_at' => $this->enrolled_at,
            'progress_percentage' => $this->progress_percentage ?? 0,
            'offering' => $this->whenLoaded('offering', fn () => [
                'id' => $this->offering->id,
                'name' => $this->offering->name,
            ]),
        ];
    }
}
