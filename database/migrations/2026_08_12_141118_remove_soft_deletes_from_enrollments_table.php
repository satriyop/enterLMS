<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single enrollment lifecycle: Spatie status (active/completed/dropped) only.
 * Soft-deleted rows are permanently removed so unique(user_id, course_id) stays meaningful.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('enrollments', 'deleted_at')) {
            DB::table('enrollments')->whereNotNull('deleted_at')->delete();

            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('enrollments', 'deleted_at')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }
};
