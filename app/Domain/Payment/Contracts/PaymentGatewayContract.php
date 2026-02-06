<?php

namespace App\Domain\Payment\Contracts;

use App\Models\Payment;

/**
 * Contract for payment gateway implementations.
 *
 * Each payment gateway (Midtrans, Stripe, etc.) implements this contract
 * to provide a consistent interface for payment processing.
 */
interface PaymentGatewayContract
{
    /**
     * Get the gateway identifier.
     */
    public function getGatewayName(): string;

    /**
     * Create a payment transaction with the gateway.
     *
     * Returns gateway-specific transaction data including payment URL.
     *
     * @return array{transaction_id: string, payment_url: string|null, expires_at: \DateTimeInterface|null, metadata: array}
     */
    public function createTransaction(Payment $payment): array;

    /**
     * Check the status of a transaction with the gateway.
     *
     * @return array{status: string, payment_type: string|null, metadata: array}
     */
    public function checkStatus(Payment $payment): array;

    /**
     * Process a refund for a paid transaction.
     *
     * @return array{refund_id: string, status: string, metadata: array}
     */
    public function refund(Payment $payment, float $amount, string $reason): array;

    /**
     * Cancel a pending/processing transaction.
     *
     * @return array{success: bool, message: string}
     */
    public function cancel(Payment $payment): array;

    /**
     * Verify a webhook/callback signature from the gateway.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;

    /**
     * Parse webhook payload into standardized format.
     *
     * @param  array<string, mixed>  $payload
     * @return array{transaction_id: string, status: string, payment_type: string|null, metadata: array}
     */
    public function parseWebhookPayload(array $payload): array;
}
