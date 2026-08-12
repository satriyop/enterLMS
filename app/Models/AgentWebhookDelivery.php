<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentWebhookDelivery extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DEAD = 'dead';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agent_webhook_endpoint_id',
        'delivery_id',
        'event_name',
        'event_id',
        'payload',
        'status',
        'attempts',
        'http_status',
        'response_body',
        'error_message',
        'delivered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'http_status' => 'integer',
            'delivered_at' => 'datetime',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(AgentWebhookEndpoint::class, 'agent_webhook_endpoint_id');
    }
}
