<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $question_bank_item_id
 * @property string $option_text
 * @property bool $is_correct
 * @property string|null $feedback
 * @property int $order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read QuestionBankItem $questionBankItem
 */
class QuestionBankOption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'question_bank_item_id',
        'option_text',
        'is_correct',
        'feedback',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function questionBankItem(): BelongsTo
    {
        return $this->belongsTo(QuestionBankItem::class);
    }
}
