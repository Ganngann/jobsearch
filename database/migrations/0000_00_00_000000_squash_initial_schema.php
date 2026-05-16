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

        $driver = Schema::getConnection()->getDriverName();
        $schemaFile = $driver === 'sqlite' ? 'squash-schema.sql' : 'squash-schema.mysql.sql';
        $path = database_path("schema/$schemaFile");
        
        if (file_exists($path)) {
            $sql = file_get_contents($path);
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
