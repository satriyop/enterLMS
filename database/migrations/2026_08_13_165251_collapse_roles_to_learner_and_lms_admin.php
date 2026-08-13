<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Collapse the role enum to the two roles CONTEXT.md actually names.
 *
 * content_manager, trainer, teaching_assistant, compliance_officer and auditor
 * were introduced for the banking/OJK positioning that ADR 004 froze. Every one
 * of them described staff work that, in this phase, only the founder does --
 * so they all fold into lms_admin.
 *
 * Tenant Admin, Tenant Owner and Operator are deliberately NOT added here:
 * ADR 005 phases them in alongside unified Enteraksi login, and an unused role
 * is the same mistake in the opposite direction.
 */
return new class extends Migration
{
    private const GHOST_ROLES = [
        'content_manager',
        'trainer',
        'teaching_assistant',
        'compliance_officer',
        'auditor',
    ];

    private const PREVIOUS_ROLES = [
        'learner',
        'content_manager',
        'trainer',
        'lms_admin',
        'compliance_officer',
        'auditor',
        'teaching_assistant',
    ];

    private const CURRENT_ROLES = [
        'learner',
        'lms_admin',
    ];

    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', self::GHOST_ROLES)
            ->update(['role' => 'lms_admin']);

        $this->setRoleEnum(self::CURRENT_ROLES);
    }

    public function down(): void
    {
        $this->setRoleEnum(self::PREVIOUS_ROLES);
    }

    /**
     * @param  list<string>  $roles
     */
    private function setRoleEnum(array $roles): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite cannot alter a check constraint in place, so the column is
            // dropped and rebuilt. Snapshot the values first -- rebuilding would
            // otherwise reset every user to the 'learner' default.
            $existing = DB::table('users')->pluck('role', 'id');

            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex('users_role_index');
            });

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('role');
            });

            Schema::table('users', function (Blueprint $table) use ($roles): void {
                $table->enum('role', $roles)->default('learner')->after('password');
                $table->index('role', 'users_role_index');
            });

            foreach ($existing as $id => $role) {
                DB::table('users')
                    ->where('id', $id)
                    ->update(['role' => in_array($role, $roles, true) ? $role : 'lms_admin']);
            }

            return;
        }

        $rolesString = implode("','", $roles);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('{$rolesString}') NOT NULL DEFAULT 'learner'");
    }
};
