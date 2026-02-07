<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $enrollment_id
 * @property int $lesson_id
 * @property int $scorm_package_id
 * @property string|null $lesson_status
 * @property string|null $completion_status
 * @property string|null $success_status
 * @property float|null $score_raw
 * @property float|null $score_min
 * @property float|null $score_max
 * @property float|null $score_scaled
 * @property string|null $total_time
 * @property string|null $session_time
 * @property string|null $suspend_data
 * @property string|null $location
 * @property string|null $exit_type
 * @property string|null $entry
 * @property array|null $cmi_data
 * @property Carbon|null $launched_at
 * @property Carbon|null $last_accessed_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Enrollment $enrollment
 * @property-read Lesson $lesson
 * @property-read ScormPackage $scormPackage
 * @property-read bool $is_completed
 * @property-read bool $is_passed
 */
class ScormScoTracking extends Model
{
    /** @use HasFactory<\Database\Factories\ScormScoTrackingFactory> */
    use HasFactory;

    protected $table = 'scorm_sco_tracking';

    protected $fillable = [
        'enrollment_id',
        'lesson_id',
        'scorm_package_id',
        'lesson_status',
        'completion_status',
        'success_status',
        'score_raw',
        'score_min',
        'score_max',
        'score_scaled',
        'total_time',
        'session_time',
        'suspend_data',
        'location',
        'exit_type',
        'entry',
        'cmi_data',
        'launched_at',
        'last_accessed_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'cmi_data' => 'array',
            'score_raw' => 'decimal:2',
            'score_min' => 'decimal:2',
            'score_max' => 'decimal:2',
            'score_scaled' => 'decimal:4',
            'launched_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function scormPackage(): BelongsTo
    {
        return $this->belongsTo(ScormPackage::class);
    }

    /**
     * Whether the SCO reports a completed state (works for both 1.2 and 2004).
     */
    public function getIsCompletedAttribute(): bool
    {
        // SCORM 2004
        if ($this->completion_status === 'completed') {
            return true;
        }

        // SCORM 1.2
        return in_array($this->lesson_status, ['completed', 'passed']);
    }

    /**
     * Whether the SCO reports a passed state (works for both 1.2 and 2004).
     */
    public function getIsPassedAttribute(): bool
    {
        // SCORM 2004
        if ($this->success_status === 'passed') {
            return true;
        }

        // SCORM 1.2
        return $this->lesson_status === 'passed';
    }
}
