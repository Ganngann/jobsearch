<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // On récupère les index existants pour éviter les doublons
        $existingIndexes = collect(Schema::getIndexes('user_matches'))->pluck('name')->toArray();

        Schema::table('user_matches', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('user_matches_user_id_vector_score_index', $existingIndexes)) {
                $table->index(['user_id', 'vector_score'], 'user_matches_user_id_vector_score_index');
            }
            if (!in_array('user_matches_user_id_pre_score_index', $existingIndexes)) {
                $table->index(['user_id', 'pre_score'], 'user_matches_user_id_pre_score_index');
            }
            if (!in_array('user_matches_user_id_final_score_index', $existingIndexes)) {
                $table->index(['user_id', 'final_score'], 'user_matches_user_id_final_score_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_matches', function (Blueprint $table) {
            $table->dropIndex('user_matches_user_id_vector_score_index');
            $table->dropIndex('user_matches_user_id_pre_score_index');
            $table->dropIndex('user_matches_user_id_final_score_index');
        });
    }
};
