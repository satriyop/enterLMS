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
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->index(
                ['enrollment_id', 'is_completed'],
                'lesson_progress_enrollment_completed_index'
            );
        });

        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->index(
                ['assessment_id', 'user_id', 'status'],
                'attempts_assessment_user_status_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropIndex('lesson_progress_enrollment_completed_index');
        });

        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropIndex('attempts_assessment_user_status_index');
        });
    }
};
