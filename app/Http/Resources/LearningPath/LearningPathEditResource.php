<?php

namespace App\Http\Resources\LearningPath;

use App\Models\LearningPath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Learning path data for edit form page.
 *
 * @mixin LearningPath
 */
class LearningPathEditResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'objectives' => $this->objectives ?? [],
            'estimated_duration' => $this->estimated_duration ?? 0,
            'difficulty_level' => $this->difficulty_level,
            'thumbnail_url' => $this->thumbnail_url,
            'courses' => $this->whenLoaded('courses', fn () => $this->courses->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->short_description,
                'estimated_duration' => $course->manual_duration_minutes ?? $course->estimated_duration_minutes ?? 0,
                'difficulty_level' => $course->difficulty_level,
                'thumbnail_url' => $course->thumbnail_path,
                'pivot' => [
                    'is_required' => $course->pivot->is_required ?? true,
                    'min_completion_percentage' => $course->pivot->min_completion_percentage,
                    'prerequisites' => $course->pivot->prerequisites,
                    'position' => $course->pivot->position ?? 0,
                ],
            ])),
        ];
    }
}
