<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->decimal('proposal_score', 8, 2)->nullable()->after('feedback');
            $table->text('proposal_feedback')->nullable()->after('proposal_score');
            $table->string('proposal_status')->nullable()->after('proposal_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropColumn(['proposal_score', 'proposal_feedback', 'proposal_status']);
        });
    }
};
