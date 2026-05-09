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
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->json('daily_ai_limits')->nullable()->after('daily_ai_limit');
            $blueprint->json('daily_ai_usage_breakdown')->nullable()->after('daily_ai_usage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['daily_ai_limits', 'daily_ai_usage_breakdown']);
        });
    }
};
