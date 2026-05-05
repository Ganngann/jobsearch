<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        $path = database_path('schema/squash-schema.sql');
        
        if (file_exists($path)) {
            $sql = file_get_contents($path);
            // Splitting by ; followed by newline to execute statement by statement
            // This is safer for some PDO drivers although unprepared() should handle it.
            DB::unprepared($sql);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No simple way to reverse a full schema dump
    }
};
