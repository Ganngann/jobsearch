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
        Schema::table('user_matches', function (Blueprint $table) {
            $table->json('pre_score_details')->nullable()->after('pre_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_matches', function (Blueprint $table) {
            $table->dropColumn('pre_score_details');
        });
    }
};
