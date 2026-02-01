<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Category model (Admin management).
 *
 * @mixin Category
 */
class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'order' => $this->order,
            'courses_count' => $this->whenCounted('courses'),
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent?->id,
                'name' => $this->parent?->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
