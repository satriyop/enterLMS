<?php

namespace App\Http\Resources\Course;

use App\Http\Resources\CourseSectionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full course detail for show/detail pages.
 *
 * Used by both instructor (courses/Show) and learner (courses/Detail) views,
 * as well as lesson sidebar (lessons/Show, courses/LessonPreview).
 *
 * @mixin \App\Models\Course
 */
class CourseShowResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'description' => $this->long_description,
            'objectives' => $this->objectives ?? [],
            'prerequisites' => $this->prerequisites ?? [],
            'status' => (string) $this->status,
            'visibility' => $this->visibility,
            'difficulty_level' => $this->difficulty_level,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'manual_duration_minutes' => $this->manual_duration_minutes,
            'thumbnail_path' => $this->thumbnail_path,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            /** @phpstan-ignore argument.type */
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn (\App\Models\Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])),
            'sections' => $this->whenLoaded('sections', fn () => CourseSectionResource::collection($this->sections->sortBy('order')->values())),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'lessons_count' => $this->whenCounted('lessons'),
            'enrollments_count' => $this->whenCounted('enrollments'),
            'ratings_count' => $this->whenCounted('ratings'),
            'average_rating' => $this->when(
                array_key_exists('ratings_avg_rating', $this->resource->getAttributes()),
                fn () => round((float) $this->resource->getAttributes()['ratings_avg_rating'], 1),
            ),
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
        ];
    }
}
