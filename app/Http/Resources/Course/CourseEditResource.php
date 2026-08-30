<?php

namespace App\Http\Resources\Course;

use App\Http\Resources\CourseSectionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Course data for edit form page.
 *
 * @mixin \App\Models\Course
 */
class CourseEditResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'objectives' => $this->objectives ?? [],
            'prerequisites' => $this->prerequisites ?? [],
            'category_id' => $this->category_id,
            'difficulty_level' => $this->difficulty_level,
            'visibility' => $this->visibility,
            'status' => (string) $this->status,
            'manual_duration_minutes' => $this->manual_duration_minutes,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            /** @phpstan-ignore argument.type */
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn (\App\Models\Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])),
            'sections' => $this->whenLoaded('sections', fn () => CourseSectionResource::collection($this->sections->sortBy('order')->values())),
        ];
    }
}
