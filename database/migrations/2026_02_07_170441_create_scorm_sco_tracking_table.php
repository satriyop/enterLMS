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
        Schema::create('scorm_sco_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scorm_package_id')->constrained()->cascadeOnDelete();

            // SCORM 1.2 status
            $table->string('lesson_status', 30)->nullable();

            // SCORM 2004 statuses
            $table->string('completion_status', 20)->nullable();
            $table->string('success_status', 20)->nullable();

            // Score fields
            $table->decimal('score_raw', 8, 2)->nullable();
            $table->decimal('score_min', 8, 2)->nullable();
            $table->decimal('score_max', 8, 2)->nullable();
            $table->decimal('score_scaled', 5, 4)->nullable();

            // Time tracking
            $table->string('total_time')->nullable();
            $table->string('session_time')->nullable();

            // State preservation
            $table->text('suspend_data')->nullable();
            $table->string('location')->nullable();

            // Session info
            $table->string('exit_type', 20)->nullable();
            $table->string('entry', 20)->nullable();

            // Catchall for additional CMI data
            $table->json('cmi_data')->nullable();

            // Timestamps
            $table->timestamp('launched_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'lesson_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scorm_sco_tracking');
    }
};
