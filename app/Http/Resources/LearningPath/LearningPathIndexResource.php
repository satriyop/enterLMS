<?php

namespace App\Http\Resources\LearningPath;

use App\Models\LearningPath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Learning path list item for admin index page.
 *
 * @mixin LearningPath
 */
class LearningPathIndexResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_published' => $this->is_published,
            'estimated_duration' => $this->estimated_duration ?? 0,
            'difficulty_level' => $this->difficulty_level,
            'thumbnail_url' => $this->thumbnail_url,
            'courses_count' => $this->whenCounted('courses'),
            'creator' => $this->whenLoaded('creator', fn () => [
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
