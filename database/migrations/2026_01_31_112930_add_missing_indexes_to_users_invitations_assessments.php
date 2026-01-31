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
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_index');
        });

        Schema::table('course_invitations', function (Blueprint $table) {
            $table->index(['course_id', 'status'], 'invitations_course_status_index');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->index(['course_id', 'status'], 'assessments_course_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
        });

        Schema::table('course_invitations', function (Blueprint $table) {
            $table->dropIndex('invitations_course_status_index');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex('assessments_course_status_index');
        });
    }
};
