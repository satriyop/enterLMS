<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $skin
 * @property int $enrollment_id
 * @property int $lesson_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Enrollment $enrollment
 * @property-read Lesson $lesson
 */
class TutorFocus extends Model
{
    /** @use HasFactory<\Database\Factories\TutorFocusFactory> */
    use HasFactory;

    public const SKIN_WHATSAPP = 'whatsapp';

    public const SKIN_TELEGRAM = 'telegram';

    /**
     * @return list<string>
     */
    public static function skins(): array
    {
        return [
            self::SKIN_WHATSAPP,
            self::SKIN_TELEGRAM,
        ];
    }

    protected $table = 'tutor_focuses';

    protected $fillable = [
        'user_id',
        'skin',
        'enrollment_id',
        'lesson_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
