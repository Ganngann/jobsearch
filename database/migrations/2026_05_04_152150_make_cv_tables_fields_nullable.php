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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // On ne revient pas en arrière car remettre NOT NULL sur des données qui pourraient être nulles échouerait
    }
};
