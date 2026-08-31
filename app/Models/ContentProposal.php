<?php

namespace App\Models;

use Database\Factories\ContentProposalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * @property int $id
 * @property int $course_id
 * @property int $lesson_id
 * @property int $asked_by
 * @property string $instruction
 * @property string $grounding_body
 * @property array<string, mixed>|null $proposed_rich_content
 * @property string|null $proposed_body_text
 * @property string|null $reason
 * @property string $status
 * @property int|null $accepted_by
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property int|null $rejected_by
 * @property \Illuminate\Support\Carbon|null $rejected_at
 */
class ContentProposal extends Model
{
    /** @use HasFactory<ContentProposalFactory> */
    use HasFactory;

    public const STATUS_ASKING = 'asking';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'course_id',
        'lesson_id',
        'asked_by',
        'instruction',
        'grounding_body',
        'proposed_rich_content',
        'proposed_body_text',
        'reason',
        'status',
        'accepted_by',
        'accepted_at',
        'rejected_by',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_rich_content' => 'array',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function askedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asked_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAsking(): bool
    {
        return $this->status === self::STATUS_ASKING;
    }

    /**
     * @param  array{type: string, content: list<array<string, mixed>>}  $richContent
     */
    public function recordDraft(string $bodyText, array $richContent, string $reason): self
    {
        if (! $this->isAsking()) {
            throw new RuntimeException('Usulan konten ini tidak menunggu jawaban Author Agent.');
        }

        $this->update([
            'proposed_body_text' => $bodyText,
            'proposed_rich_content' => $richContent,
            'reason' => $reason,
            'status' => self::STATUS_PENDING,
        ]);

        return $this->fresh() ?? $this;
    }

    public function markFailed(): self
    {
        $this->update(['status' => self::STATUS_FAILED]);

        return $this->fresh() ?? $this;
    }

    public function accept(User $admin): self
    {
        if (! $this->isPending()) {
            throw new RuntimeException('Usulan konten ini tidak menunggu keputusan.');
        }

        if ($this->proposed_rich_content === null || trim((string) $this->proposed_body_text) === '') {
            throw new RuntimeException('Usulan konten tidak memiliki isi.');
        }

        DB::transaction(function () use ($admin): void {
            $lesson = Lesson::query()->lockForUpdate()->findOrFail($this->lesson_id);

            $firstParagraph = str($this->proposed_body_text)->trim()->explode("\n")->first() ?? '';

            $lesson->update([
                'rich_content' => $this->proposed_rich_content,
                'description' => str($firstParagraph)->limit(500)->toString(),
            ]);

            $this->update([
                'status' => self::STATUS_ACCEPTED,
                'accepted_by' => $admin->id,
                'accepted_at' => now(),
            ]);
        });

        return $this->fresh() ?? $this;
    }

    public function reject(User $admin): self
    {
        if (! $this->isPending()) {
            throw new RuntimeException('Usulan konten ini tidak menunggu keputusan.');
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_by' => $admin->id,
            'rejected_at' => now(),
        ]);

        return $this->fresh() ?? $this;
    }
}
