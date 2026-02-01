<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SoftDeletes columns added conditionally (may already exist from partial run)
        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('enrollments', 'deleted_at')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('course_ratings', 'deleted_at')) {
            Schema::table('course_ratings', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Performance indexes
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index('completed_at');
        });

        Schema::table('course_ratings', function (Blueprint $table) {
            $table->index(['course_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('course_ratings', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'created_at']);
            $table->dropSoftDeletes();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['completed_at']);
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
