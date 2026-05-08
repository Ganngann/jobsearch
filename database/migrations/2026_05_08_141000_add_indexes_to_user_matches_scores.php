<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_matches', function (Blueprint $table) {
            $table->index(['user_id', 'vector_score']);
            $table->index(['user_id', 'pre_score']);
            $table->index(['user_id', 'final_score']);
        });
    }

    public function down(): void
    {
        Schema::table('user_matches', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'vector_score']);
            $table->dropIndex(['user_id', 'pre_score']);
            $table->dropIndex(['user_id', 'final_score']);
        });
    }
};
