<?php

namespace App\Http\Resources;

use App\Models\CourseSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CourseSection
 */
class CourseSectionResource extends JsonResource
{
    /**
     * Disable wrapping for Inertia compatibility.
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'order' => $this->order,
            'description' => $this->description,
            'lessons' => $this->whenLoaded('lessons', fn () => $this->lessons->sortBy('order')->values()->map(fn ($lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'content_type' => $lesson->content_type,
                'is_free_preview' => $lesson->is_free_preview,
                'order' => $lesson->order,
                'estimated_duration_minutes' => $lesson->estimated_duration_minutes,
            ])),
        ];
    }
}
