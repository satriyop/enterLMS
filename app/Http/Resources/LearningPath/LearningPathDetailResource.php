<?php

namespace App\Http\Resources\LearningPath;

use App\Models\LearningPath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full learning path detail for admin show page.
 *
 * @mixin LearningPath
 */
class LearningPathDetailResource extends JsonResource
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
            'is_published' => $this->is_published,
            'estimated_duration' => $this->estimated_duration ?? 0,
            'difficulty_level' => $this->difficulty_level,
            'thumbnail_url' => $this->thumbnail_url,
            'creator' => $this->whenLoaded('creator', fn () => [
                'name' => $this->creator->name,
            ]),
            /** @phpstan-ignore return.type, argument.unresolvableType */
            'courses' => $this->whenLoaded('courses', fn () => $this->courses->map(fn (\App\Models\Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->short_description,
                'estimated_duration' => $course->manual_duration_minutes ?? $course->estimated_duration_minutes ?? 0,
                'difficulty_level' => $course->difficulty_level,
                'thumbnail_url' => $course->thumbnail_path,
                'sections_count' => $course->sections_count ?? 0,
                'enrollments_count' => $course->enrollments_count ?? 0,
                'pivot' => [
                    'is_required' => $course->pivot->is_required ?? true, /** @phpstan-ignore property.notFound */
                    'min_completion_percentage' => $course->pivot->min_completion_percentage, /** @phpstan-ignore property.notFound */
                    'prerequisites' => $course->pivot->prerequisites, /** @phpstan-ignore property.notFound */
                ],
            ])),
        ];
    }
}
