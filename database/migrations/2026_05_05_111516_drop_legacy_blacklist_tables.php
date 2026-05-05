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
        Schema::dropIfExists('user_blacklisted_metiers');
        Schema::dropIfExists('user_blacklisted_referentiel');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('user_blacklisted_metiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('metier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('user_blacklisted_referentiel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referentiel_metier_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->timestamps();
        });
    }
};
