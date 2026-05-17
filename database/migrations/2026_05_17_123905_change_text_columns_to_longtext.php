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
        // Users table
        Schema::table('users', function (Blueprint $table) {
            $table->longText('profile_text')->nullable()->change();
            $table->longText('aspirations')->nullable()->change();
            $table->longText('links')->nullable()->change();
        });

        // User Matches table
        Schema::table('user_matches', function (Blueprint $table) {
            $table->longText('strengths')->nullable()->change();
            $table->longText('weaknesses')->nullable()->change();
            $table->longText('ai_raw_response')->nullable()->change();
            $table->longText('ai_analysis_narrative')->nullable()->change();
            $table->longText('ai_recommendation')->nullable()->change();
            $table->longText('pre_score_details')->nullable()->change();
        });

        // Job Offers table
        Schema::table('job_offers', function (Blueprint $table) {
            $table->longText('description')->nullable()->change();
            $table->longText('benefits_comments')->nullable()->change();
            $table->longText('locations_json')->nullable()->change();
            $table->longText('apply_instructions')->nullable()->change();
        });

        // Profile Messages table
        Schema::table('profile_messages', function (Blueprint $table) {
            $table->longText('content')->change();
        });

        // Discovery Suggestions table
        Schema::table('discovery_suggestions', function (Blueprint $table) {
            $table->longText('reason')->change();
        });

        // User Feedback table
        Schema::table('user_feedback', function (Blueprint $table) {
            $table->longText('message')->change();
        });

        // User Facts table
        Schema::table('user_facts', function (Blueprint $table) {
            $table->longText('content')->nullable()->change();
            $table->longText('source_metadata')->nullable()->change();
            $table->longText('proposed_content')->nullable()->change();
        });

        // Profile Entities tables
        Schema::table('experiences', function (Blueprint $table) {
            $table->longText('description')->nullable()->change();
            $table->longText('proposed_data')->nullable()->change();
        });

        Schema::table('education', function (Blueprint $table) {
            $table->longText('description')->nullable()->change();
            $table->longText('proposed_data')->nullable()->change();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->longText('description')->nullable()->change();
            $table->longText('proposed_data')->nullable()->change();
        });

        Schema::table('certifications', function (Blueprint $table) {
            $table->longText('proposed_data')->nullable()->change();
        });

        Schema::table('volunteer_experiences', function (Blueprint $table) {
            $table->longText('description')->nullable()->change();
            $table->longText('proposed_data')->nullable()->change();
        });

        Schema::table('interests', function (Blueprint $table) {
            $table->longText('proposed_data')->nullable()->change();
        });

        // Referentiel Metiers
        Schema::table('referentiel_metiers', function (Blueprint $table) {
            $table->longText('description')->nullable()->change();
        });

        // Employers
        Schema::table('employers', function (Blueprint $table) {
            $table->longText('description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
