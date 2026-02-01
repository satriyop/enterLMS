<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\Course
 */
class HomeCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'thumbnail_url' => $this->thumbnail_path
                ? Storage::url($this->thumbnail_path)
                : null,
            'instructor' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'rating' => round((float) ($this->ratings_avg_rating ?? 0), 1),
            'reviews_count' => $this->ratings_count ?? 0,
            'students_count' => $this->enrollments_count ?? 0,
            'estimated_duration_minutes' => $this->duration,
            'lessons_count' => $this->lessons_count ?? 0,
            'difficulty_level' => $this->difficulty_level,
            'is_bestseller' => ($this->enrollments_count ?? 0) > 100,
            'is_new' => $this->created_at?->isAfter(now()->subDays(30)),
        ];
    }
}
