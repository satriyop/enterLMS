<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->foreignId('enrollment_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();
        });

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $attempts = DB::table('assessment_attempts')->select('id', 'user_id', 'assessment_id')->get();

            foreach ($attempts as $attempt) {
                $courseId = DB::table('assessments')->where('id', $attempt->assessment_id)->value('course_id');

                if ($courseId === null) {
                    continue;
                }

                $enrollmentId = DB::table('enrollments')
                    ->where('user_id', $attempt->user_id)
                    ->where('course_id', $courseId)
                    ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'completed' THEN 1 ELSE 2 END")
                    ->orderByDesc('enrolled_at')
                    ->value('id');

                if ($enrollmentId) {
                    DB::table('assessment_attempts')->where('id', $attempt->id)->update([
                        'enrollment_id' => $enrollmentId,
                    ]);
                }
            }

            return;
        }

        DB::statement("
            UPDATE assessment_attempts
            INNER JOIN assessments ON assessments.id = assessment_attempts.assessment_id
            LEFT JOIN enrollments ON enrollments.id = (
                SELECT e.id FROM enrollments e
                WHERE e.user_id = assessment_attempts.user_id
                  AND e.course_id = assessments.course_id
                ORDER BY CASE e.status WHEN 'active' THEN 0 WHEN 'completed' THEN 1 ELSE 2 END, e.enrolled_at DESC
                LIMIT 1
            )
            SET assessment_attempts.enrollment_id = enrollments.id
        ");
    }

    public function down(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enrollment_id');
        });
    }
};
