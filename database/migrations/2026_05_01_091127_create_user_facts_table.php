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
        Schema::create('user_facts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->string('category')->default('other'); // strength, weakness, value, taste, achievement, context
            $table->string('status')->default('draft'); // draft, validated, rejected
            $table->float('confidence_score')->nullable();
            $table->json('source_metadata')->nullable(); // info on which message triggered this
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_facts');
    }
};
