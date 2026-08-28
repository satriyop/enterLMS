<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $conversation_id
 * @property string $role
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Conversation $conversation
 */
class ConversationTurn extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationTurnFactory> */
    use HasFactory;

    public const ROLE_LEARNER = 'learner';

    public const ROLE_TUTOR = 'tutor';

    protected $fillable = [
        'conversation_id',
        'role',
        'body',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
