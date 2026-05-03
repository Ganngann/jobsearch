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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('validated')->after('user_id');
        });
        Schema::table('certifications', function (Blueprint $table) {
            $table->string('status')->default('validated')->after('user_id');
        });
        Schema::table('interests', function (Blueprint $table) {
            $table->string('status')->default('validated')->after('user_id');
        });
        Schema::table('volunteer_experiences', function (Blueprint $table) {
            $table->string('status')->default('validated')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) { $table->dropColumn('status'); });
        Schema::table('certifications', function (Blueprint $table) { $table->dropColumn('status'); });
        Schema::table('interests', function (Blueprint $table) { $table->dropColumn('status'); });
        Schema::table('volunteer_experiences', function (Blueprint $table) { $table->dropColumn('status'); });
    }
};
