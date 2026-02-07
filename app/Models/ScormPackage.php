<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property string $version
 * @property string|null $identifier
 * @property string $original_filename
 * @property string $disk
 * @property string $extraction_path
 * @property string $entry_point
 * @property array|null $metadata
 * @property int $file_size_bytes
 * @property int $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $uploader
 * @property-read bool $is_scorm_12
 * @property-read bool $is_scorm_2004
 */
class ScormPackage extends Model
{
    /** @use HasFactory<\Database\Factories\ScormPackageFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'version',
        'identifier',
        'original_filename',
        'disk',
        'extraction_path',
        'entry_point',
        'metadata',
        'file_size_bytes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'file_size_bytes' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function trackingRecords(): HasMany
    {
        return $this->hasMany(ScormScoTracking::class);
    }

    public function getIsScorm12Attribute(): bool
    {
        return $this->version === '1.2';
    }

    public function getIsScorm2004Attribute(): bool
    {
        return str_starts_with($this->version, '2004');
    }
}
