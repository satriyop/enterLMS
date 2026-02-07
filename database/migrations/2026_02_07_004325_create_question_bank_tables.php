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
        // Question Bank Items - reusable questions
        Schema::create('question_bank_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('question_type', [
                'multiple_choice',
                'true_false',
                'matching',
                'short_answer',
                'essay',
                'file_upload',
            ]);
            $table->integer('default_points')->default(1);
            $table->text('feedback')->nullable();
            $table->text('correct_answer')->nullable();
            $table->boolean('case_sensitive')->default(false);
            $table->text('grading_rubric')->nullable();
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->enum('visibility', ['private', 'department', 'public'])->default('private');
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for filtering and searching
            $table->index(['user_id', 'visibility']);
            $table->index('question_type');
            $table->index('difficulty_level');
        });

        // Question Bank Options - options for multiple choice, matching, etc.
        Schema::create('question_bank_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_item_id')->constrained()->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->text('feedback')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('question_bank_item_id');
        });

        // Pivot table for question bank items and tags
        Schema::create('question_bank_item_tag', function (Blueprint $table) {
            $table->foreignId('question_bank_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

            $table->primary(['question_bank_item_id', 'tag_id']);
        });

        // Add source tracking to questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('source_question_bank_item_id')
                ->nullable()
                ->after('order')
                ->constrained('question_bank_items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['source_question_bank_item_id']);
            $table->dropColumn('source_question_bank_item_id');
        });

        Schema::dropIfExists('question_bank_item_tag');
        Schema::dropIfExists('question_bank_options');
        Schema::dropIfExists('question_bank_items');
    }
};
