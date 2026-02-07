<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $statement_id
 * @property int|null $actor_id
 * @property string|null $actor_mbox
 * @property string|null $actor_name
 * @property string $verb_id
 * @property string $verb_display
 * @property string $object_type
 * @property string $object_id
 * @property string|null $object_name
 * @property float|null $result_score_raw
 * @property float|null $result_score_min
 * @property float|null $result_score_max
 * @property float|null $result_score_scaled
 * @property bool|null $result_success
 * @property bool|null $result_completion
 * @property string|null $result_duration
 * @property string|null $context_registration
 * @property int|null $context_course_id
 * @property int|null $context_enrollment_id
 * @property array|null $context_extensions
 * @property Carbon|null $timestamp
 * @property Carbon|null $stored
 * @property string $source
 * @property-read User|null $actor
 */
class XapiStatement extends Model
{
    /** @use HasFactory<\Database\Factories\XapiStatementFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'statement_id',
        'actor_id',
        'actor_mbox',
        'actor_name',
        'verb_id',
        'verb_display',
        'object_type',
        'object_id',
        'object_name',
        'result_score_raw',
        'result_score_min',
        'result_score_max',
        'result_score_scaled',
        'result_success',
        'result_completion',
        'result_duration',
        'context_registration',
        'context_course_id',
        'context_enrollment_id',
        'context_extensions',
        'timestamp',
        'stored',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'context_extensions' => 'array',
            'result_success' => 'boolean',
            'result_completion' => 'boolean',
            'result_score_raw' => 'decimal:2',
            'result_score_min' => 'decimal:2',
            'result_score_max' => 'decimal:2',
            'result_score_scaled' => 'decimal:4',
            'timestamp' => 'datetime',
            'stored' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
