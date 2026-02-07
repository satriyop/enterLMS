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
        Schema::create('xapi_statements', function (Blueprint $table) {
            $table->id();
            $table->uuid('statement_id')->unique();

            // Actor
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_mbox')->nullable();
            $table->string('actor_name')->nullable();

            // Verb
            $table->string('verb_id');
            $table->string('verb_display');

            // Object
            $table->string('object_type')->default('Activity');
            $table->string('object_id');
            $table->string('object_name')->nullable();

            // Result
            $table->decimal('result_score_raw', 8, 2)->nullable();
            $table->decimal('result_score_min', 8, 2)->nullable();
            $table->decimal('result_score_max', 8, 2)->nullable();
            $table->decimal('result_score_scaled', 5, 4)->nullable();
            $table->boolean('result_success')->nullable();
            $table->boolean('result_completion')->nullable();
            $table->string('result_duration')->nullable();

            // Context
            $table->uuid('context_registration')->nullable();
            $table->unsignedBigInteger('context_course_id')->nullable();
            $table->unsignedBigInteger('context_enrollment_id')->nullable();
            $table->json('context_extensions')->nullable();

            // Metadata
            $table->timestamp('timestamp')->nullable();
            $table->timestamp('stored')->nullable();
            $table->string('source', 20)->default('native');

            $table->index(['actor_id', 'verb_id']);
            $table->index(['object_id']);
            $table->index(['context_course_id']);
            $table->index(['source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xapi_statements');
    }
};
