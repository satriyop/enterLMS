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
 * @property string $payable_type
 * @property int $payable_id
 * @property int $user_id
 * @property int|null $enrollment_id
 * @property float $amount
 * @property string $currency
 * @property string $status
 * @property string|null $gateway
 * @property string|null $gateway_transaction_id
 * @property string|null $gateway_payment_type
 * @property string|null $gateway_status
 * @property string|null $payment_url
 * @property string|null $redirect_url
 * @property Carbon|null $paid_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $refunded_at
 * @property float|null $refund_amount
 * @property string|null $refund_reason
 * @property int|null $refunded_by
 * @property array|null $metadata
 * @property string|null $invoice_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read User|null $refunder
 * @property-read Enrollment|null $enrollment
 * @property-read Model $payable
 *
 * @method static Builder|Payment pending()
 * @method static Builder|Payment paid()
 * @method static Builder|Payment forUser(User $user)
 * @method static Builder|Payment forCourse(Course $course)
 */
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_PAID,
        self::STATUS_FAILED,
        self::STATUS_EXPIRED,
        self::STATUS_REFUNDED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'payable_type',
        'payable_id',
        'user_id',
        'enrollment_id',
        'amount',
        'currency',
        'status',
        'gateway',
        'gateway_transaction_id',
        'gateway_payment_type',
        'gateway_status',
        'payment_url',
        'redirect_url',
        'paid_at',
        'expires_at',
        'refunded_at',
        'refund_amount',
        'refund_reason',
        'refunded_by',
        'metadata',
        'invoice_number',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refunder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeForCourse(Builder $query, Course $course): Builder
    {
        return $query->where('payable_type', Course::class)
            ->where('payable_id', $course->id);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // State Methods
    // ─────────────────────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if payment can be processed.
     */
    public function canProcess(): bool
    {
        return $this->isPending();
    }

    /**
     * Check if payment can be marked as paid.
     */
    public function canMarkPaid(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true);
    }

    /**
     * Check if payment can be refunded.
     */
    public function canRefund(): bool
    {
        return $this->isPaid();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Status Transition Methods
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Mark payment as processing.
     */
    public function markProcessing(
        ?string $gatewayTransactionId = null,
        ?string $paymentUrl = null
    ): self {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'gateway_transaction_id' => $gatewayTransactionId ?? $this->gateway_transaction_id,
            'payment_url' => $paymentUrl ?? $this->payment_url,
        ]);

        return $this;
    }

    /**
     * Mark payment as paid.
     */
    public function markPaid(
        ?string $gatewayTransactionId = null,
        ?string $gatewayPaymentType = null,
        ?array $metadata = null
    ): self {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'gateway_transaction_id' => $gatewayTransactionId ?? $this->gateway_transaction_id,
            'gateway_payment_type' => $gatewayPaymentType ?? $this->gateway_payment_type,
            'metadata' => $metadata ? array_merge($this->metadata ?? [], $metadata) : $this->metadata,
        ]);

        return $this;
    }

    /**
     * Mark payment as failed.
     */
    public function markFailed(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => array_merge($this->metadata ?? [], ['failure_reason' => $reason]),
        ]);

        return $this;
    }

    /**
     * Mark payment as expired.
     */
    public function markExpired(): self
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);

        return $this;
    }

    /**
     * Mark payment as cancelled.
     */
    public function markCancelled(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'metadata' => array_merge($this->metadata ?? [], ['cancellation_reason' => $reason]),
        ]);

        return $this;
    }

    /**
     * Process refund.
     */
    public function refund(User $refundedBy, float $amount, string $reason): self
    {
        $this->update([
            'status' => self::STATUS_REFUNDED,
            'refunded_at' => now(),
            'refund_amount' => $amount,
            'refund_reason' => $reason,
            'refunded_by' => $refundedBy->id,
        ]);

        return $this;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');

        $count = static::query()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        return sprintf('%s-%s%s-%05d', $prefix, $year, $month, $count);
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 0, ',', '.').' '.$this->currency;
    }

    /**
     * Check if payment is for a course.
     */
    public function isForCourse(): bool
    {
        return $this->payable_type === Course::class;
    }

    /**
     * Get the course if this payment is for a course.
     */
    public function getCourse(): ?Course
    {
        if (! $this->isForCourse()) {
            return null;
        }

        /** @var Course */
        return $this->payable;
    }
}
