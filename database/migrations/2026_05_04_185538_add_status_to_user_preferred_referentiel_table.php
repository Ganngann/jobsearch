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
        Schema::table('user_preferred_referentiel', function (Blueprint $table) {
            $table->string('status')->default('favorite')->after('referentiel_metier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferred_referentiel', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
