<?php

namespace App\Domain\Agent\Services;

use App\Models\AgentWebhookDelivery;
use App\Models\AgentWebhookEndpoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Signs and delivers outbound agent webhooks (HMAC-SHA256).
 */
class AgentWebhookDispatcher
{
    public const HEADER_SIGNATURE = 'X-Enteraksi-Signature';

    public const HEADER_EVENT = 'X-Enteraksi-Event';

    public const HEADER_DELIVERY = 'X-Enteraksi-Delivery';

    /**
     * @param  array<string, mixed>  $payload
     * @return list<AgentWebhookDelivery>
     */
    public function dispatch(string $eventName, array $payload, ?string $eventId = null): array
    {
        $endpoints = AgentWebhookEndpoint::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (AgentWebhookEndpoint $e) => $e->listensFor($eventName));

        $deliveries = [];

        foreach ($endpoints as $endpoint) {
            $deliveries[] = $this->deliver($endpoint, $eventName, $payload, $eventId);
        }

        return $deliveries;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function deliver(
        AgentWebhookEndpoint $endpoint,
        string $eventName,
        array $payload,
        ?string $eventId = null,
    ): AgentWebhookDelivery {
        $deliveryId = (string) Str::uuid();
        $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = $this->sign($body, $endpoint->secret);

        $delivery = AgentWebhookDelivery::query()->create([
            'agent_webhook_endpoint_id' => $endpoint->id,
            'delivery_id' => $deliveryId,
            'event_name' => $eventName,
            'event_id' => $eventId,
            'payload' => $payload,
            'status' => AgentWebhookDelivery::STATUS_PENDING,
            'attempts' => 0,
        ]);

        $maxAttempts = max(1, (int) $endpoint->max_attempts);
        $lastError = null;
        $lastStatus = null;
        $lastBody = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $delivery->update(['attempts' => $attempt]);

            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        self::HEADER_SIGNATURE => 'sha256='.$signature,
                        self::HEADER_EVENT => $eventName,
                        self::HEADER_DELIVERY => $deliveryId,
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'Enteraksi-AgentWebhook/1.0',
                    ])
                    ->withBody($body, 'application/json')
                    ->post($endpoint->url);

                $lastStatus = $response->status();
                $lastBody = mb_substr($response->body(), 0, 2000);

                if ($response->successful()) {
                    $delivery->update([
                        'status' => AgentWebhookDelivery::STATUS_SUCCESS,
                        'http_status' => $lastStatus,
                        'response_body' => $lastBody,
                        'error_message' => null,
                        'delivered_at' => now(),
                    ]);
                    $endpoint->update(['last_success_at' => now()]);

                    return $delivery->fresh();
                }

                $lastError = "HTTP {$lastStatus}";
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning('agent.webhook.delivery_failed', [
                    'endpoint_id' => $endpoint->id,
                    'delivery_id' => $deliveryId,
                    'attempt' => $attempt,
                    'error' => $lastError,
                ]);
            }
        }

        $status = AgentWebhookDelivery::STATUS_DEAD;
        $delivery->update([
            'status' => $status,
            'http_status' => $lastStatus,
            'response_body' => $lastBody,
            'error_message' => $lastError !== null ? mb_substr($lastError, 0, 1000) : null,
        ]);
        $endpoint->update(['last_failure_at' => now()]);

        return $delivery->fresh();
    }

    public function sign(string $body, string $secret): string
    {
        return hash_hmac('sha256', $body, $secret);
    }

    public function verify(string $body, string $secret, string $headerValue): bool
    {
        $provided = $headerValue;
        if (str_starts_with($provided, 'sha256=')) {
            $provided = substr($provided, 7);
        }

        return hash_equals($this->sign($body, $secret), $provided);
    }
}
