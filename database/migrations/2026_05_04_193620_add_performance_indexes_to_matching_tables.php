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
        // Indexes pour JobOffers (indispensables pour les GROUP BY et JOIN)
        Schema::table('job_offers', function (Blueprint $table) {
            $table->index('metier_id');
            $table->index('employer_id');
            $table->index('published_at');
        });

        // Indexes pour UserMatches (indispensables pour le TRI)
        Schema::table('user_matches', function (Blueprint $table) {
            $table->index('pre_score');
            $table->index('final_score');
            $table->index(['user_id', 'pre_score']); // Index composé pour filtrage rapide
            $table->index(['user_id', 'final_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->dropIndex(['metier_id']);
            $table->dropIndex(['employer_id']);
            $table->dropIndex(['published_at']);
        });

        Schema::table('user_matches', function (Blueprint $table) {
            $table->dropIndex(['pre_score']);
            $table->dropIndex(['final_score']);
            $table->dropIndex(['user_id', 'pre_score']);
            $table->dropIndex(['user_id', 'final_score']);
        });
    }
};
