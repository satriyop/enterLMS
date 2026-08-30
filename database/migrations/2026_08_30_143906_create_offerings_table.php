<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->foreignId('facilitator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['course_id', 'code']);
            $table->index(['course_id', 'is_default']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('offering_id')
                ->nullable()
                ->after('course_id')
                ->constrained()
                ->restrictOnDelete();
        });

        $now = now();

        foreach (DB::table('courses')->select('id', 'title', 'max_enrollments')->orderBy('id')->get() as $course) {
            $offeringId = DB::table('offerings')->insertGetId([
                'course_id' => $course->id,
                'name' => $course->title,
                'code' => 'default',
                'capacity' => $course->max_enrollments,
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('enrollments')
                ->where('course_id', $course->id)
                ->update(['offering_id' => $offeringId]);
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'course_id']);
            $table->unique(['user_id', 'offering_id']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'offering_id']);
            $table->dropConstrainedForeignId('offering_id');
            $table->unique(['user_id', 'course_id']);
        });

        Schema::dropIfExists('offerings');
    }
};
