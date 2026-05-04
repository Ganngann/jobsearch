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
            $table->text('content')->nullable()->change();
            $table->string('category')->nullable()->change();
        });

        Schema::table('experiences', function (Blueprint $table) {
            $table->string('company')->nullable()->change();
            $table->string('title')->nullable()->change();
            $table->date('start_date')->nullable()->change();
        });

        Schema::table('education', function (Blueprint $table) {
            $table->string('school')->nullable()->change();
            $table->string('degree')->nullable()->change();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        Schema::table('certifications', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('issuing_organization')->nullable()->change();
        });

        Schema::table('volunteer_experiences', function (Blueprint $table) {
            $table->string('organization')->nullable()->change();
            $table->string('role')->nullable()->change();
        });
        
        Schema::table('interests', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
