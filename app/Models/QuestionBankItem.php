<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $question_text
 * @property string $question_type
 * @property int $default_points
 * @property string|null $feedback
 * @property string|null $correct_answer
 * @property bool $case_sensitive
 * @property string|null $grading_rubric
 * @property string|null $difficulty_level
 * @property string $visibility
 * @property int $times_used
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, QuestionBankOption> $options
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tag> $tags
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Question> $derivedQuestions
 */
class QuestionBankItem extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (QuestionBankItem $item) {
            if ($item->isForceDeleting()) {
                $item->options()->forceDelete();
            } else {
                $item->options()->delete();
            }
        });

        static::restoring(function (QuestionBankItem $item) {
            /** @phpstan-ignore method.notFound (onlyTrashed from SoftDeletes) */
            $item->options()->onlyTrashed()->restore();
        });
    }

    protected $fillable = [
        'user_id',
        'question_text',
        'question_type',
        'default_points',
        'feedback',
        'correct_answer',
        'case_sensitive',
        'grading_rubric',
        'difficulty_level',
        'visibility',
        'times_used',
    ];

    protected function casts(): array
    {
        return [
            'case_sensitive' => 'boolean',
            'times_used' => 'integer',
            'default_points' => 'integer',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionBankOption::class)->orderBy('order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'question_bank_item_tag');
    }

    /**
     * Questions that were imported from this bank item.
     */
    public function derivedQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'source_question_bank_item_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to items visible to a specific user.
     * - Own items (any visibility)
     * - Department items (if user is instructor/content_manager/lms_admin)
     * - Public items
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            // Own items
            $q->where('user_id', $user->id);

            // Public items
            $q->orWhere('visibility', 'public');

            // Department items for instructors+
            if (in_array($user->role, ['lms_admin', 'content_manager', 'trainer'])) {
                $q->orWhere('visibility', 'department');
            }
        });
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('question_type', $type);
    }

    public function scopeOfDifficulty(Builder $query, string $difficulty): Builder
    {
        return $query->where('difficulty_level', $difficulty);
    }

    public function scopeWithTag(Builder $query, int $tagId): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tagId) {
            $q->where('tags.id', $tagId);
        });
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where('question_text', 'like', "%{$search}%");
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    public function getQuestionTypeLabel(): string
    {
        return match ($this->question_type) {
            'multiple_choice' => 'Pilihan Ganda',
            'true_false' => 'Benar/Salah',
            'matching' => 'Pencocokan',
            'short_answer' => 'Jawaban Singkat',
            'essay' => 'Esai',
            'file_upload' => 'Unggah Berkas',
            default => 'Tidak Diketahui',
        };
    }

    public function getDifficultyLabel(): ?string
    {
        return match ($this->difficulty_level) {
            'beginner' => 'Pemula',
            'intermediate' => 'Menengah',
            'advanced' => 'Lanjutan',
            default => null,
        };
    }

    public function getVisibilityLabel(): string
    {
        return match ($this->visibility) {
            'private' => 'Pribadi',
            'department' => 'Departemen',
            'public' => 'Publik',
            default => 'Tidak Diketahui',
        };
    }

    public function isMultipleChoice(): bool
    {
        return $this->question_type === 'multiple_choice';
    }

    public function isTrueFalse(): bool
    {
        return $this->question_type === 'true_false';
    }

    public function isMatching(): bool
    {
        return $this->question_type === 'matching';
    }

    public function isShortAnswer(): bool
    {
        return $this->question_type === 'short_answer';
    }

    public function isEssay(): bool
    {
        return $this->question_type === 'essay';
    }

    public function isFileUpload(): bool
    {
        return $this->question_type === 'file_upload';
    }

    public function requiresManualGrading(): bool
    {
        return $this->isEssay() || $this->isFileUpload();
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function isVisibleTo(User $user): bool
    {
        // Owner can always see
        if ($this->user_id === $user->id) {
            return true;
        }

        // Public is visible to all
        if ($this->visibility === 'public') {
            return true;
        }

        // Department visibility for instructors+
        if ($this->visibility === 'department') {
            return in_array($user->role, ['lms_admin', 'content_manager', 'trainer']);
        }

        return false;
    }

    public function incrementTimesUsed(int $count = 1): void
    {
        $this->increment('times_used', $count);
    }

    /**
     * Sync options for this bank item.
     *
     * @param  array<int, array{id?: int|null, option_text: string, is_correct: bool, feedback?: string|null, order?: int}>  $optionsData
     */
    public function syncOptions(array $optionsData): void
    {
        $existingOptionIds = $this->options()->pluck('id')->toArray();
        $submittedOptionIds = [];

        foreach ($optionsData as $optionData) {
            if (isset($optionData['id']) && $optionData['id'] > 0) {
                /** @var QuestionBankOption|null $option */
                $option = $this->options()->find($optionData['id']);
                if ($option) {
                    $option->update([
                        'option_text' => $optionData['option_text'],
                        'is_correct' => $optionData['is_correct'] ?? false,
                        'feedback' => $optionData['feedback'] ?? null,
                        'order' => $optionData['order'] ?? 0,
                    ]);
                    $submittedOptionIds[] = $option->id;
                }
            } else {
                /** @var QuestionBankOption $option */
                $option = $this->options()->create([
                    'option_text' => $optionData['option_text'],
                    'is_correct' => $optionData['is_correct'] ?? false,
                    'feedback' => $optionData['feedback'] ?? null,
                    'order' => $optionData['order'] ?? 0,
                ]);
                $submittedOptionIds[] = $option->id;
            }
        }

        $optionsToDelete = array_diff($existingOptionIds, $submittedOptionIds);
        if (! empty($optionsToDelete)) {
            $this->options()->whereIn('id', $optionsToDelete)->delete();
        }
    }
}
