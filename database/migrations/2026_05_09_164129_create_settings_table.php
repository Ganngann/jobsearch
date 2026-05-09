<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insertion des tarifs par défaut
        DB::table('settings')->insert([
            [
                'key' => 'ai_rate_flash_in',
                'value' => '0.10',
                'group' => 'ai_pricing',
                'description' => 'Prix par million de tokens entrants (Flash/Flash Lite)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ai_rate_flash_out',
                'value' => '0.30',
                'group' => 'ai_pricing',
                'description' => 'Prix par million de tokens sortants (Flash/Flash Lite)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ai_rate_embedding_in',
                'value' => '0.025',
                'group' => 'ai_pricing',
                'description' => 'Prix par million de tokens entrants (Embedding)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
