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
        Schema::create('scorm_packages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('version', 10); // '1.2' or '2004'
            $table->string('identifier')->nullable();
            $table->string('original_filename');
            $table->string('disk')->default('local');
            $table->string('extraction_path');
            $table->string('entry_point');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scorm_packages');
    }
};
