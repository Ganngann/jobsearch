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
        Schema::create('discovery_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code', 10);
            $table->string('title');
            $table->text('reason');
            $table->string('type')->default('aligned'); // aligned or surprise
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovery_suggestions');
    }
};
