<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $enrollment_id
 * @property int $lesson_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Enrollment $enrollment
 * @property-read Lesson $lesson
 * @property-read Collection<int, ConversationTurn> $turns
 */
class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'lesson_id',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function turns(): HasMany
    {
        return $this->hasMany(ConversationTurn::class)->orderBy('id');
    }
}
