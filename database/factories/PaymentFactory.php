<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payable_type' => Course::class,
            'payable_id' => Course::factory()->published(),
            'user_id' => User::factory()->learner(),
            'amount' => $this->faker->randomFloat(2, 50000, 500000),
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
            'gateway' => 'midtrans',
            'invoice_number' => 'INV-TEST-'.$this->faker->unique()->numerify('######'),
        ];
    }

    /**
     * Payment for a specific course.
     */
    public function forCourse(Course $course): static
    {
        return $this->state(fn () => [
            'payable_type' => Course::class,
            'payable_id' => $course->id,
            'amount' => $course->price ?? 100000,
            'currency' => $course->currency ?? 'IDR',
        ]);
    }

    /**
     * Pending payment.
     */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    /**
     * Processing payment.
     */
    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_PROCESSING,
            'gateway_transaction_id' => 'TXN-'.strtoupper($this->faker->bothify('??###???')),
            'payment_url' => 'https://payment.example.com/'.$this->faker->uuid(),
        ]);
    }

    /**
     * Paid/completed payment.
     */
    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
            'gateway_transaction_id' => 'TXN-'.strtoupper($this->faker->bothify('??###???')),
            'gateway_payment_type' => $this->faker->randomElement(['credit_card', 'bank_transfer', 'gopay']),
        ]);
    }

    /**
     * Failed payment.
     */
    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_FAILED,
            'metadata' => ['failure_reason' => 'Insufficient funds'],
        ]);
    }

    /**
     * Expired payment.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_EXPIRED,
            'expires_at' => now()->subHours(24),
        ]);
    }

    /**
     * Refunded payment.
     */
    public function refunded(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_REFUNDED,
            'paid_at' => now()->subDays(5),
            'refunded_at' => now(),
            'refund_amount' => fn (array $attrs) => $attrs['amount'],
            'refund_reason' => 'Customer request',
            'refunded_by' => User::factory()->lmsAdmin(),
        ]);
    }

    /**
     * Cancelled payment.
     */
    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_CANCELLED,
            'metadata' => ['cancellation_reason' => 'User cancelled'],
        ]);
    }

    /**
     * Payment with expiration.
     */
    public function withExpiration(int $hours = 24): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->addHours($hours),
        ]);
    }

    /**
     * Payment using Midtrans gateway.
     */
    public function midtrans(): static
    {
        return $this->state(fn () => [
            'gateway' => 'midtrans',
        ]);
    }

    /**
     * Payment using Stripe gateway.
     */
    public function stripe(): static
    {
        return $this->state(fn () => [
            'gateway' => 'stripe',
        ]);
    }
}
