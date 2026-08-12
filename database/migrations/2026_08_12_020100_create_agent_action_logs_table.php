<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('token_id')->nullable()->index();
            $table->string('tool', 120)->index();
            $table->string('status', 20)->index();
            $table->json('arguments')->nullable();
            $table->string('error_message', 1000)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['tool', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_action_logs');
    }
};
