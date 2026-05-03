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
        Schema::table('experiences', function (Blueprint $table) {
            $table->string('status')->default('validated')->after('user_id'); // draft, validated
            $table->string('proposed_action')->nullable()->after('status'); // add, update, delete
        });

        Schema::table('education', function (Blueprint $table) {
            $table->string('status')->default('validated')->after('user_id');
            $table->string('proposed_action')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropColumn(['status', 'proposed_action']);
        });

        Schema::table('education', function (Blueprint $table) {
            $table->dropColumn(['status', 'proposed_action']);
        });
    }
};
