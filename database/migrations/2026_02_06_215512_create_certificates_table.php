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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            // Core certificate data
            $table->string('certificate_number', 50)->unique();
            $table->enum('type', ['course_completion', 'assessment_pass', 'learning_path_completion'])->default('course_completion');
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active');

            // Polymorphic relationship (can be for course, assessment, or learning path)
            $table->string('certificable_type');
            $table->unsignedBigInteger('certificable_id');

            // Recipient
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Optionally linked to enrollment for course certificates
            $table->foreignId('enrollment_id')->nullable()->constrained()->cascadeOnDelete();

            // Certificate data (snapshot at issue time)
            $table->string('recipient_name');
            $table->string('certificable_title');
            $table->decimal('grade', 5, 2)->nullable();
            $table->unsignedInteger('progress_percentage')->nullable();

            // Issuance metadata
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();

            // Revocation (if applicable)
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revocation_reason')->nullable();

            // Verification
            $table->string('verification_code', 32)->unique();

            $table->timestamps();

            // Indexes
            $table->index(['certificable_type', 'certificable_id']);
            $table->index('user_id');
            $table->index('issued_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
