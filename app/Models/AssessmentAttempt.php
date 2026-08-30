<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $assessment_id
 * @property int $user_id
 * @property int|null $enrollment_id
 * @property int $attempt_number
 * @property string $status
 * @property int|null $score
 * @property int|null $max_score
 * @property float|null $percentage
 * @property bool|null $passed
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $graded_at
 * @property int|null $graded_by
 * @property string|null $feedback
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Assessment $assessment
 * @property-read User $user
 * @property-read Enrollment|null $enrollment
 * @property-read User|null $gradedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AttemptAnswer> $answers
 */
class AssessmentAttempt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'user_id',
        'enrollment_id',
        'attempt_number',
        'status',
        'score',
        'max_score',
        'percentage',
        'passed',
        'started_at',
        'submitted_at',
        'graded_at',
        'graded_by',
        'feedback',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'passed' => 'boolean',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function requiresGrading(): bool
    {
        return $this->isSubmitted() && ! $this->isGraded();
    }

    public function calculateScore(): void
    {
        $totalScore = $this->answers()->sum('score');
        $this->assessment->loadSum('questions', 'points');
        $maxScore = $this->assessment->total_points;
        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;
        $passed = $percentage >= $this->assessment->passing_score;

        $this->update([
            'score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'passed' => $passed,
            'status' => 'graded',
            'graded_at' => now(),
        ]);
    }

    public function completeAttempt(): void
    {
        $this->update([
            'status' => 'completed',
        ]);
    }
}
