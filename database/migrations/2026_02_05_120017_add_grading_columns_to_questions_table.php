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
            // Used by ShortAnswerGradingStrategy and TrueFalseGradingStrategy
            // Comma-separated acceptable answers for short answer questions
            // Boolean value (as string) for true/false questions
            $table->text('correct_answer')->nullable()->after('feedback');

            // Used by ShortAnswerGradingStrategy
            // Whether answer matching should be case-sensitive
            $table->boolean('case_sensitive')->default(false)->after('correct_answer');

            // Used by ManualGradingStrategy
            // Rubric/guidelines for manual grading of essays, file uploads, etc.
            $table->text('grading_rubric')->nullable()->after('case_sensitive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['correct_answer', 'case_sensitive', 'grading_rubric']);
        });
    }
};
