<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Offering;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Offering a user facilitates, for the learner home (Kelas saya).
 *
 * @mixin Offering
 */
class DashboardFacilitatedOfferingResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'is_default' => $this->is_default,
            'enrollments_count' => $this->enrollments_count ?? 0,
            'course' => [
                'id' => $this->course->id,
                'title' => $this->course->title,
            ],
        ];
    }
}
