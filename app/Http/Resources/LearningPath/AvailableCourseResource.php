<?php

namespace App\Http\Resources\LearningPath;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight course info for learning path course picker.
 *
 * @mixin Course
 */
class AvailableCourseResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->short_description,
            'estimated_duration' => $this->manual_duration_minutes ?? $this->estimated_duration_minutes ?? 0,
            'difficulty_level' => $this->difficulty_level,
            'thumbnail_url' => $this->thumbnail_path,
        ];
    }
}
