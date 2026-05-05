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
        Schema::dropIfExists('user_blacklisted_skills');
        Schema::dropIfExists('fact_skill');
        if (Schema::hasColumn('user_matches', 'is_blacklisted')) {
            Schema::table('user_matches', function (Blueprint $table) {
                $table->dropIndex('user_matches_is_blacklisted_index');
                $table->dropColumn('is_blacklisted');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
