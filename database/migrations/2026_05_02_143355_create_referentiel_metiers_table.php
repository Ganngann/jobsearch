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
        Schema::create('referentiel_metiers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->index(); // Code ROME (ex: M1805)
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('family_name')->nullable(); // Famille (ex: Support technique)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referentiel_metiers');
    }
};
