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
            $table->timestamp('profile_published_at')->nullable()->after('last_ai_usage_at');
        });

        Schema::table('user_matches', function (Blueprint $table) {
            $table->integer('ai_at_pre_score')->nullable()->after('ai_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_published_at');
        });

        Schema::table('user_matches', function (Blueprint $table) {
            $table->dropColumn('ai_at_pre_score');
        });
    }
};
