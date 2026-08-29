<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds data integrity constraints:
     * - Foreign key on enrollments.last_lesson_id
     * - Check constraints for bounded values (passing_score, max_attempts, progress_percentage)
     *
     * Note: Unique ordering constraints (e.g., course_sections.course_id + order) are intentionally
     * omitted. SQLite doesn't support deferred constraints, so batch reorder operations (CASE-based
     * UPDATEs that swap order values) would fail. Order integrity is enforced at the application level.
     */
    public function up(): void
    {
        // Original column was integer; lessons.id is bigint unsigned.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->unsignedBigInteger('last_lesson_id')->nullable()->change();
            });
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('last_lesson_id')
                ->references('id')
                ->on('lessons')
                ->nullOnDelete();
        });

        // Check constraints for bounded values
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE TRIGGER check_assessment_passing_score_insert
                BEFORE INSERT ON assessments
                FOR EACH ROW
                WHEN NEW.passing_score IS NOT NULL AND (NEW.passing_score < 0 OR NEW.passing_score > 100)
                BEGIN
                    SELECT RAISE(ABORT, "passing_score must be between 0 and 100");
                END');

            DB::statement('CREATE TRIGGER check_assessment_passing_score_update
                BEFORE UPDATE ON assessments
                FOR EACH ROW
                WHEN NEW.passing_score IS NOT NULL AND (NEW.passing_score < 0 OR NEW.passing_score > 100)
                BEGIN
                    SELECT RAISE(ABORT, "passing_score must be between 0 and 100");
                END');

            DB::statement('CREATE TRIGGER check_assessment_max_attempts_insert
                BEFORE INSERT ON assessments
                FOR EACH ROW
                WHEN NEW.max_attempts IS NOT NULL AND NEW.max_attempts < 0
                BEGIN
                    SELECT RAISE(ABORT, "max_attempts must be >= 0");
                END');

            DB::statement('CREATE TRIGGER check_assessment_max_attempts_update
                BEFORE UPDATE ON assessments
                FOR EACH ROW
                WHEN NEW.max_attempts IS NOT NULL AND NEW.max_attempts < 0
                BEGIN
                    SELECT RAISE(ABORT, "max_attempts must be >= 0");
                END');

            DB::statement('CREATE TRIGGER check_enrollment_progress_insert
                BEFORE INSERT ON enrollments
                FOR EACH ROW
                WHEN NEW.progress_percentage IS NOT NULL AND (NEW.progress_percentage < 0 OR NEW.progress_percentage > 100)
                BEGIN
                    SELECT RAISE(ABORT, "progress_percentage must be between 0 and 100");
                END');

            DB::statement('CREATE TRIGGER check_enrollment_progress_update
                BEFORE UPDATE ON enrollments
                FOR EACH ROW
                WHEN NEW.progress_percentage IS NOT NULL AND (NEW.progress_percentage < 0 OR NEW.progress_percentage > 100)
                BEGIN
                    SELECT RAISE(ABORT, "progress_percentage must be between 0 and 100");
                END');
        } else {
            // MySQL/PostgreSQL - use native CHECK constraints
            DB::statement('ALTER TABLE assessments ADD CONSTRAINT chk_passing_score CHECK (passing_score IS NULL OR (passing_score >= 0 AND passing_score <= 100))');
            DB::statement('ALTER TABLE assessments ADD CONSTRAINT chk_max_attempts CHECK (max_attempts IS NULL OR max_attempts >= 0)');
            DB::statement('ALTER TABLE enrollments ADD CONSTRAINT chk_progress_percentage CHECK (progress_percentage IS NULL OR (progress_percentage >= 0 AND progress_percentage <= 100))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove check constraints / triggers
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS check_assessment_passing_score_insert');
            DB::statement('DROP TRIGGER IF EXISTS check_assessment_passing_score_update');
            DB::statement('DROP TRIGGER IF EXISTS check_assessment_max_attempts_insert');
            DB::statement('DROP TRIGGER IF EXISTS check_assessment_max_attempts_update');
            DB::statement('DROP TRIGGER IF EXISTS check_enrollment_progress_insert');
            DB::statement('DROP TRIGGER IF EXISTS check_enrollment_progress_update');
        } else {
            DB::statement('ALTER TABLE assessments DROP CONSTRAINT IF EXISTS chk_passing_score');
            DB::statement('ALTER TABLE assessments DROP CONSTRAINT IF EXISTS chk_max_attempts');
            DB::statement('ALTER TABLE enrollments DROP CONSTRAINT IF EXISTS chk_progress_percentage');
        }

        // Remove foreign key
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['last_lesson_id']);
        });
    }
};
