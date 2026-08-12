<?php

namespace App\Models;

use App\Domain\Assessment\Exceptions\MaxAttemptsReachedException;
use App\Models\Concerns\RequiresEagerLoading;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Assessment extends Model
{
    use HasFactory, RequiresEagerLoading, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Assessment $assessment) {
            if ($assessment->isForceDeleting()) {
                $assessment->questions()->forceDelete();
                $assessment->attempts()->forceDelete();
            } else {
                $assessment->questions()->delete();
                // Attempts are historical records — preserve on soft-delete
            }
        });

        static::restoring(function (Assessment $assessment) {
            /** @phpstan-ignore method.notFound (onlyTrashed from SoftDeletes) */
            $assessment->questions()->onlyTrashed()->restore();
        });
    }

    protected $fillable = [
        'course_id',
        'user_id',
        'title',
        'slug',
        'description',
        'instructions',
        'time_limit_minutes',
        'passing_score',
        'max_attempts',
        'shuffle_questions',
        'show_correct_answers',
        'allow_review',
        'is_required',
        'status',
        'visibility',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'shuffle_questions' => 'boolean',
            'show_correct_answers' => 'boolean',
            'allow_review' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Get total questions count.
     * Requires withCount('questions') to be loaded on the query.
     */
    public function getTotalQuestionsAttribute(): int
    {
        return $this->getEagerCount('questions');
    }

    /**
     * Get total points.
     * Requires withSum('questions', 'points') to be loaded on the query.
     */
    public function getTotalPointsAttribute(): int
    {
        return (int) $this->getEagerSum('questions', 'points');
    }

    public function getIsEditableAttribute(): bool
    {
        return $this->status !== 'published';
    }

    public function generateSlug(): string
    {
        return Str::slug($this->title).'-'.Str::random(6);
    }

    public function canBeAttemptedBy(User $user): bool
    {
        // Check if assessment is published
        if ($this->status !== 'published') {
            return false;
        }

        // Check if user is enrolled in the course and has content access
        $enrollment = $user->enrollments()->where('course_id', $this->course_id)->first();
        if (! $enrollment || ! $enrollment->canAccessContent()) {
            return false;
        }

        // Allow resuming a single open attempt without counting it as a "new" start.
        if ($this->getOpenAttemptFor($user) !== null) {
            return true;
        }

        // Count finished + open attempts toward the limit (no unlimited parallel starts).
        $usedAttempts = $this->attempts()->where('user_id', $user->id)
            ->whereIn('status', ['in_progress', 'submitted', 'graded', 'completed'])
            ->count();

        if ($this->max_attempts > 0 && $usedAttempts >= $this->max_attempts) {
            return false;
        }

        return true;
    }

    /**
     * Get the user's open (in_progress) attempt for this assessment, if any.
     */
    public function getOpenAttemptFor(User $user): ?AssessmentAttempt
    {
        /** @var AssessmentAttempt|null $attempt */
        $attempt = $this->attempts()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        return $attempt;
    }

    /**
     * Determine if any question in this assessment requires manual grading.
     */
    public function requiresManualGrading(): bool
    {
        return $this->questions()
            ->whereIn('question_type', ['essay', 'file_upload'])
            ->exists();
    }

    /**
     * Validate that user can attempt this assessment.
     * Throws specific exceptions for each failure reason.
     *
     * @throws MaxAttemptsReachedException
     */
    public function validateAttemptOrFail(User $user): void
    {
        if ($this->max_attempts > 0) {
            $usedAttempts = $this->attempts()
                ->where('user_id', $user->id)
                ->whereIn('status', ['in_progress', 'submitted', 'graded', 'completed'])
                ->count();

            if ($usedAttempts >= $this->max_attempts) {
                throw new MaxAttemptsReachedException(
                    userId: $user->id,
                    assessmentId: $this->id,
                    maxAttempts: $this->max_attempts,
                    completedAttempts: $usedAttempts
                );
            }
        }
    }

    /**
     * Get the number of finished attempts for a user (submitted/graded/completed).
     */
    public function getCompletedAttemptsCount(User $user): int
    {
        return $this->attempts()
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'graded', 'completed'])
            ->count();
    }
}
