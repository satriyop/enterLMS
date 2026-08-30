<?php

namespace App\Models;

use App\Domain\Course\Exceptions\CannotDeleteDefaultOfferingException;
use App\Domain\Course\Exceptions\OfferingHasEnrollmentsException;
use App\Domain\Enrollment\States\ActiveState;
use App\Domain\Enrollment\States\CompletedState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $course_id
 * @property string $name
 * @property string $code
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int|null $capacity
 * @property int|null $facilitator_id
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Course $course
 * @property-read User|null $facilitator
 * @property-read Collection<int, Enrollment> $enrollments
 */
class Offering extends Model
{
    /** @use HasFactory<\Database\Factories\OfferingFactory> */
    use HasFactory;

    public const DEFAULT_CODE = 'default';

    protected $fillable = [
        'course_id',
        'name',
        'code',
        'starts_at',
        'ends_at',
        'capacity',
        'facilitator_id',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_default' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function isOpenForEnrollment(?Carbon $at = null): bool
    {
        $at ??= now();

        if ($this->starts_at !== null && $at->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $at->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function isAtCapacity(): bool
    {
        if ($this->capacity === null) {
            return false;
        }

        $taken = $this->enrollments()
            ->whereIn('status', [ActiveState::$name, CompletedState::$name])
            ->count();

        return $taken >= $this->capacity;
    }

    /**
     * @throws CannotDeleteDefaultOfferingException
     * @throws OfferingHasEnrollmentsException
     */
    public function deleteRun(): void
    {
        if ($this->is_default) {
            throw new CannotDeleteDefaultOfferingException($this->id);
        }

        if ($this->enrollments()->exists()) {
            throw new OfferingHasEnrollmentsException($this->id);
        }

        $this->delete();
    }

    public static function uniqueCodeFor(Course $course, string $name, ?string $code = null, ?int $ignoreOfferingId = null): string
    {
        $base = $code !== null && $code !== ''
            ? Str::slug($code)
            : Str::slug($name);

        if ($base === '' || $base === self::DEFAULT_CODE) {
            $base = 'run';
        }

        $candidate = $base;
        $i = 2;

        while ($course->offerings()
            ->where('code', $candidate)
            ->when($ignoreOfferingId, fn ($query) => $query->where('id', '!=', $ignoreOfferingId))
            ->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}
