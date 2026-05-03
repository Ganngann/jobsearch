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
        Schema::table('user_facts', function (Blueprint $table) {
            $table->unsignedInteger('local_id')->nullable()->after('user_id');
            $table->unique(['user_id', 'local_id']);
        });

        // Initialiser les local_id pour les données existantes
        $users = \App\Models\User::whereHas('facts')->get();
        foreach ($users as $user) {
            $facts = \App\Models\UserFact::where('user_id', $user->id)->orderBy('id')->get();
            foreach ($facts as $index => $fact) {
                $fact->update(['local_id' => $index + 1]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_facts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'local_id']);
            $table->dropColumn('local_id');
        });
    }
};
