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
        Schema::table('user_matches', function (Blueprint $col) {
            $col->text('ai_analysis_narrative')->nullable()->after('ai_raw_response');
            $col->text('ai_recommendation')->nullable()->after('ai_analysis_narrative');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_matches', function (Blueprint $col) {
            $col->dropColumn(['ai_analysis_narrative', 'ai_recommendation']);
        });
    }
};
