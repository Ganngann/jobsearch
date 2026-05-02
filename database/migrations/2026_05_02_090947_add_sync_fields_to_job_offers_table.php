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
        Schema::table('job_offers', function (Blueprint $table) {
            $table->string('status')->default('active')->after('is_detailed');
            $table->timestamp('last_seen_at')->nullable()->after('status');
            $table->timestamp('detailed_at')->nullable()->after('last_seen_at');
            $table->string('content_hash')->nullable()->after('detailed_at');

            // Index pour optimiser le Pull Worker (Priorisation des scans)
            $table->index(['status', 'is_detailed', 'last_seen_at', 'detailed_at'], 'idx_sync_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->dropIndex('idx_sync_priority');
            $table->dropColumn(['status', 'last_seen_at', 'detailed_at', 'content_hash']);
        });
    }
};
