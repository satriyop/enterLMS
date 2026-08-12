<?php

namespace Tests\Support;

use App\Domain\Payment\Contracts\PaymentGatewayContract;
use App\Models\Payment;

/**
 * Test double for PaymentGatewayContract (no real network calls).
 */
class FakePaymentGateway implements PaymentGatewayContract
{
    public function getGatewayName(): string
    {
        return 'fake';
    }

    public function createTransaction(Payment $payment): array
    {
        return [
            'transaction_id' => 'FAKE-'.$payment->id,
            'payment_url' => 'https://pay.example.test/checkout/'.$payment->id,
            'expires_at' => now()->addHours(24),
            'metadata' => [],
        ];
    }

    public function checkStatus(Payment $payment): array
    {
        return [
            'status' => 'pending',
            'payment_type' => null,
            'metadata' => [],
        ];
    }

    public function refund(Payment $payment, float $amount, string $reason): array
    {
        return [
            'refund_id' => 'REFUND-FAKE-'.$payment->id,
            'status' => 'refunded',
            'metadata' => ['reason' => $reason, 'amount' => $amount],
        ];
    }

    public function cancel(Payment $payment): array
    {
        return [
            'success' => true,
            'message' => 'Cancelled',
        ];
    }

    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        return $signature === 'valid-signature';
    }

    public function parseWebhookPayload(array $payload): array
    {
        return [
            'transaction_id' => (string) ($payload['transaction_id'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'pending'),
            'payment_type' => $payload['payment_type'] ?? null,
            'metadata' => $payload,
        ];
    }
}
