<?php

namespace App\Http\Resources\Course;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Course list item for paginated index/browse pages.
 *
 * Used by both admin (courses/Index) and learner (courses/Browse) views.
 * Each view types only the fields it needs on the frontend.
 *
 * @mixin \App\Models\Course
 */
class CourseListResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'thumbnail_path' => $this->thumbnail_path,
            'status' => (string) $this->status,
            'visibility' => $this->visibility,
            'difficulty_level' => $this->difficulty_level,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'manual_duration_minutes' => $this->manual_duration_minutes,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'sections_count' => $this->whenCounted('sections'),
            'lessons_count' => $this->whenCounted('lessons'),
            'enrollments_count' => $this->whenCounted('enrollments'),
            'ratings_count' => $this->whenCounted('ratings'),
            'ratings_avg_rating' => round((float) ($this->ratings_avg_rating ?? 0), 1),
            'created_at' => $this->created_at,
        ];
    }
}
