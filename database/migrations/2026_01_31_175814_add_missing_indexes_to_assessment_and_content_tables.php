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
        Schema::table('questions', function (Blueprint $table) {
            $table->index('assessment_id');
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->index('question_id');
        });

        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->index(['assessment_attempt_id', 'question_id'], 'attempt_answers_attempt_question_idx');
        });

        Schema::table('course_sections', function (Blueprint $table) {
            $table->index('course_id');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->index('course_section_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['assessment_id']);
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->dropIndex(['question_id']);
        });

        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropIndex('attempt_answers_attempt_question_idx');
        });

        Schema::table('course_sections', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex(['course_section_id']);
        });
    }
};
