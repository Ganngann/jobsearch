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
            $table->boolean('is_blacklisted')->default(false)->after('final_score');
            $table->index('is_blacklisted'); // Index pour filtrage rapide sur le dashboard
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_matches', function (Blueprint $table) {
            $table->dropIndex(['is_blacklisted']);
            $table->dropColumn('is_blacklisted');
        });
    }
};
