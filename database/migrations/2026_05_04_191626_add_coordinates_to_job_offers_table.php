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
        Schema::table('job_offers', function (Blueprint $blueprint) {
            $blueprint->decimal('latitude', 10, 8)->nullable()->after('location');
            $blueprint->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $blueprint->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $blueprint) {
            $blueprint->dropIndex(['latitude', 'longitude']);
            $blueprint->dropColumn(['latitude', 'longitude']);
        });
    }
};
