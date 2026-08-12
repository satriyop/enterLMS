<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentWebhookEndpoint extends Model
{
    /**
     * Supported outbound event keys.
     *
     * @var list<string>
     */
    public const SUPPORTED_EVENTS = [
        'enrollment.created',
        'enrollment.completed',
        'certificate.issued',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'max_attempts',
        'last_success_at',
        'last_failure_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'max_attempts' => 'integer',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AgentWebhookDelivery::class);
    }

    public function listensFor(string $eventName): bool
    {
        return $this->is_active && in_array($eventName, $this->events ?? [], true);
    }
}
