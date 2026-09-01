<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $certificate_number
 * @property string $type
 * @property string $status
 * @property string $certificable_type
 * @property int $certificable_id
 * @property int $user_id
 * @property int|null $enrollment_id
 * @property string $recipient_name
 * @property string $certificable_title
 * @property float|null $grade
 * @property int|null $progress_percentage
 * @property Carbon $issued_at
 * @property Carbon|null $expires_at
 * @property int|null $issued_by
 * @property Carbon|null $revoked_at
 * @property int|null $revoked_by
 * @property string|null $revocation_reason
 * @property string $verification_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read User|null $issuer
 * @property-read User|null $revoker
 * @property-read Enrollment|null $enrollment
 * @property-read Model $certificable
 *
 * @method static Builder|Certificate active()
 * @method static Builder|Certificate forUser(User $user)
 * @method static Builder|Certificate courseCompletion()
 */
class Certificate extends Model
{
    /** @use HasFactory<\Database\Factories\CertificateFactory> */
    use HasFactory;

    public const TYPE_COURSE_COMPLETION = 'course_completion';

    public const TYPE_LEARNING_PATH_COMPLETION = 'learning_path_completion';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'certificate_number',
        'type',
        'status',
        'certificable_type',
        'certificable_id',
        'user_id',
        'enrollment_id',
        'recipient_name',
        'certificable_title',
        'grade',
        'progress_percentage',
        'issued_at',
        'expires_at',
        'issued_by',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'verification_code',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'decimal:2',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function certificable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @param  Builder<Certificate>  $query
     * @return Builder<Certificate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @param  Builder<Certificate>  $query
     * @return Builder<Certificate>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<Certificate>  $query
     * @return Builder<Certificate>
     */
    public function scopeCourseCompletion(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_COURSE_COMPLETION);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // State Methods
    // ─────────────────────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Revoke this certificate.
     */
    public function revoke(User $revokedBy, string $reason): self
    {
        $this->update([
            'status' => self::STATUS_REVOKED,
            'revoked_at' => now(),
            'revoked_by' => $revokedBy->id,
            'revocation_reason' => $reason,
        ]);

        return $this;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Generate a unique certificate number.
     */
    public static function generateCertificateNumber(): string
    {
        $prefix = 'CERT';
        $year = now()->format('Y');
        $month = now()->format('m');

        // Get count of certificates this month + 1
        $count = static::query()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        return sprintf('%s-%s%s-%05d', $prefix, $year, $month, $count);
    }

    /**
     * Generate a unique verification code.
     */
    public static function generateVerificationCode(): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes(8)));
        } while (static::where('verification_code', $code)->exists());

        return $code;
    }

    /**
     * Get the verification URL for this certificate.
     */
    public function getVerificationUrl(): string
    {
        return route('certificates.verify', $this->verification_code);
    }
}
