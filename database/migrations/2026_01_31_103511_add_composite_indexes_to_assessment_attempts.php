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
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->index(['user_id', 'assessment_id'], 'attempts_user_assessment_index');
            $table->index(['assessment_id', 'status'], 'attempts_assessment_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropIndex('attempts_user_assessment_index');
            $table->dropIndex('attempts_assessment_status_index');
        });
    }
};
