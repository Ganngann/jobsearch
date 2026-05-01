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
            $table->string('proposed_action')->nullable()->after('proposed_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_facts', function (Blueprint $table) {
            $table->dropColumn('proposed_action');
        });
    }
};
