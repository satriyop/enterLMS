<?php

namespace App\Models;

use App\Models\Concerns\RequiresEagerLoading;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $course_id
 * @property string $title
 * @property string|null $description
 * @property int $order
 * @property int|null $estimated_duration_minutes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Course $course
 * @property-read Collection<int, Lesson> $lessons
 */
class CourseSection extends Model
{
    use HasFactory, RequiresEagerLoading, SoftDeletes;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order',
        'estimated_duration_minutes',
    ];

    protected static function booted(): void
    {
        // Cascade soft delete to lessons when section is deleted
        static::deleting(function (CourseSection $section) {
            if ($section->isForceDeleting()) {
                // Force delete lessons if section is being force deleted
                $section->lessons()->forceDelete();
            } else {
                // Soft delete lessons when section is soft deleted
                $section->lessons()->delete();
            }
        });

        // Restore lessons when section is restored
        static::restoring(function (CourseSection $section) {
            $section->lessons()->onlyTrashed()->restore();
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    /**
     * Requires: ->withCount('lessons') in your query.
     */
    public function getTotalLessonsAttribute(): int
    {
        return $this->getEagerCount('lessons');
    }

    public function getDurationAttribute(): int
    {
        if ($this->estimated_duration_minutes) {
            return $this->estimated_duration_minutes;
        }

        return $this->calculateEstimatedDuration();
    }

    /**
     * Calculate total estimated duration from lessons.
     *
     * Uses single SQL query instead of loading all lessons.
     */
    public function calculateEstimatedDuration(): int
    {
        return (int) DB::table('lessons')
            ->where('course_section_id', $this->id)
            ->whereNull('deleted_at')
            ->sum('estimated_duration_minutes');
    }

    public function updateEstimatedDuration(): void
    {
        $this->update([
            'estimated_duration_minutes' => $this->calculateEstimatedDuration(),
        ]);
    }

    /**
     * @param  array<int, int>  $sectionIds  Ordered array of section IDs
     */
    public static function bulkUpdateOrder(Course $course, array $sectionIds): void
    {
        if (empty($sectionIds)) {
            return;
        }

        $cases = collect($sectionIds)
            ->map(fn ($id, $order) => 'WHEN '.(int) $id.' THEN '.($order + 1))
            ->join(' ');

        DB::table('course_sections')
            ->where('course_id', $course->id)
            ->whereIn('id', $sectionIds)
            ->update(['order' => DB::raw("CASE id {$cases} END")]);
    }
}
