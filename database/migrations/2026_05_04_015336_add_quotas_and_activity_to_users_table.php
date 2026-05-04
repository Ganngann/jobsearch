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
            $table->integer('daily_ai_limit')->default(15)->after('remember_token');
            $table->integer('daily_ai_usage')->default(0)->after('daily_ai_limit');
            $table->timestamp('last_seen_at')->nullable()->after('daily_ai_usage');
            $table->timestamp('last_ai_usage_at')->nullable()->after('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['daily_ai_limit', 'daily_ai_usage', 'last_seen_at', 'last_ai_usage_at']);
        });
    }
};
