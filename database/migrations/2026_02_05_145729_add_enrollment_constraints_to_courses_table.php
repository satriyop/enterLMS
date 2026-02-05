<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dateTime('enrollment_deadline')->nullable()->after('price_valid_until')
                ->comment('Last date/time to enroll in the course');
            $table->unsignedInteger('max_enrollments')->nullable()->after('enrollment_deadline')
                ->comment('Maximum number of active enrollments allowed');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['enrollment_deadline', 'max_enrollments']);
        });
    }
};
