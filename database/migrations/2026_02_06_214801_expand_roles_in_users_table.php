<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Expands the role enum to include new enterprise roles:
     * - compliance_officer: Manages compliance, views audit reports
     * - auditor: Read-only access to audit logs
     * - teaching_assistant: Limited trainer capabilities
     */
    public function up(): void
    {
        $roles = User::ROLES;
        $rolesString = implode("','", $roles);

        if (DB::getDriverName() === 'sqlite') {
            // SQLite: Drop index first, then drop and recreate column with new check constraint
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_role_index');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            Schema::table('users', function (Blueprint $table) use ($roles) {
                $table->enum('role', $roles)->default('learner')->after('password');
                $table->index('role', 'users_role_index');
            });
        } else {
            // MySQL/MariaDB: Modify the enum in place
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('{$rolesString}') NOT NULL DEFAULT 'learner'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $originalRoles = ['learner', 'content_manager', 'trainer', 'lms_admin'];
        $rolesString = implode("','", $originalRoles);

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_role_index');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            Schema::table('users', function (Blueprint $table) use ($originalRoles) {
                $table->enum('role', $originalRoles)->default('learner')->after('password');
                $table->index('role', 'users_role_index');
            });
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('{$rolesString}') NOT NULL DEFAULT 'learner'");
        }
    }
};
