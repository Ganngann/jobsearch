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
        $tables = ['experiences', 'education', 'projects', 'certifications', 'volunteer_experiences', 'interests'];
        
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'proposed_data')) {
                    $table->json('proposed_data')->nullable();
                }
                if (!Schema::hasColumn($table->getTable(), 'proposed_action')) {
                    $table->string('proposed_action')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = ['experiences', 'education', 'projects', 'certifications', 'volunteer_experiences', 'interests'];
        
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['proposed_data', 'proposed_action']);
            });
        }
    }
};
