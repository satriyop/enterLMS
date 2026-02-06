<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Payable item (polymorphic - can be course, learning path, etc.)
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');

            // Payer
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Linked enrollment (created after payment success)
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();

            // Payment details
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('IDR');
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'expired', 'refunded', 'cancelled'])
                ->default('pending');

            // Gateway info
            $table->string('gateway')->nullable(); // midtrans, stripe, etc.
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_payment_type')->nullable(); // credit_card, bank_transfer, etc.
            $table->string('gateway_status')->nullable(); // Raw status from gateway

            // Payment URLs (for redirect-based payments)
            $table->text('payment_url')->nullable();
            $table->text('redirect_url')->nullable();

            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // Refund details
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->string('refund_reason')->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();

            // Metadata (for gateway-specific data, invoice info, etc.)
            $table->json('metadata')->nullable();

            // Invoice number (for bookkeeping)
            $table->string('invoice_number', 50)->nullable()->unique();

            $table->timestamps();

            // Indexes
            $table->index(['payable_type', 'payable_id']);
            $table->index('user_id');
            $table->index('status');
            $table->index('gateway_transaction_id');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
