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
            $table->foreignId('experience_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_facts', function (Blueprint $table) {
            $table->dropForeign(['experience_id']);
            $table->dropColumn(['experience_id']);
        });
    }
};
