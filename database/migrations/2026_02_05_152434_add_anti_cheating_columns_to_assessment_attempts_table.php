<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds IP address and user agent tracking for anti-cheating compliance.
     * These fields are captured when an assessment attempt is started.
     */
    public function up(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('feedback');
            $table->string('user_agent', 512)->nullable()->after('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }
};
