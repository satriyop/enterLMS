<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentActionLog extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_ERROR = 'error';

    public const STATUS_DENIED = 'denied';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'token_id',
        'tool',
        'status',
        'arguments',
        'error_message',
        'duration_ms',
        'meta',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'meta' => 'array',
            'duration_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
