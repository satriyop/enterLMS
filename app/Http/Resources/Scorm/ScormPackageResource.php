<?php

namespace App\Http\Resources\Scorm;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ScormPackage
 */
class ScormPackageResource extends JsonResource
{
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
            'version' => $this->version,
            'identifier' => $this->identifier,
            'original_filename' => $this->original_filename,
            'entry_point' => $this->entry_point,
            'metadata' => $this->metadata,
            'file_size_bytes' => $this->file_size_bytes,
            'uploaded_by' => $this->uploaded_by,
            'is_scorm_12' => $this->is_scorm_12,
            'is_scorm_2004' => $this->is_scorm_2004,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
