<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lesson
 */
class LessonResource extends JsonResource
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
            'description' => $this->description,
            'order' => $this->order,
            'content_type' => $this->content_type,
            'rich_content' => $this->rich_content,
            'youtube_url' => $this->youtube_url,
            'youtube_video_id' => $this->youtube_video_id,
            'conference_url' => $this->conference_url,
            'conference_type' => $this->conference_type,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'is_free_preview' => $this->is_free_preview,
            'rich_content_html' => $this->when(
                $this->resource->hasAppended('rich_content_html'),
                fn () => $this->rich_content_html
            ),
            'section' => $this->whenLoaded('section', fn () => [
                'id' => $this->section->id,
                'title' => $this->section->title,
                'course' => $this->when(
                    $this->section->relationLoaded('course'),
                    fn () => [
                        'id' => $this->section->course->id,
                        'title' => $this->section->course->title,
                        'slug' => $this->section->course->slug,
                    ]
                ),
            ]),
            'media' => $this->whenLoaded('media', fn () => MediaResource::collection($this->media)),
        ];
    }
}
