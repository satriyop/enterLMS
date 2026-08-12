<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('url', 500);
            $table->string('secret', 128);
            $table->json('events'); // list of event keys e.g. enrollment.completed
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('max_attempts')->default(3);
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_webhook_endpoint_id')->constrained()->cascadeOnDelete();
            $table->uuid('delivery_id')->unique();
            $table->string('event_name', 100)->index();
            $table->string('event_id', 64)->nullable()->index();
            $table->json('payload');
            $table->string('status', 20)->index(); // pending, success, failed, dead
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['agent_webhook_endpoint_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_webhook_deliveries');
        Schema::dropIfExists('agent_webhook_endpoints');
    }
};
