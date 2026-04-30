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
        // Reference Tables
        Schema::create('metiers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->index(); // Code ROME
            $table->string('guid')->nullable();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('code')->index();
            $table->string('label');
            $table->enum('type', ['hard', 'soft']);
            $table->timestamps();
        });

        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->timestamps();
        });

        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->string('id_forem')->nullable()->index();
            $table->string('label');
            $table->string('logo_uuid')->nullable();
            $table->text('logo_base64')->nullable();
            $table->string('logo_mime_type')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('permits', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('studies', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->timestamps();
        });

        // Main Table: Job Offers (renamed from 'jobs' to avoid conflict with Laravel queue)
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->integer('forem_id')->unique();
            $table->string('forem_ref')->unique();
            $table->string('title');
            $table->foreignId('metier_id')->nullable()->constrained('metiers');
            $table->foreignId('employer_id')->constrained('employers');
            $table->foreignId('source_id')->nullable()->constrained('sources');
            $table->text('description');
            $table->string('contract_type');
            $table->string('working_regime');
            $table->string('working_regime_detail')->nullable();
            $table->decimal('working_hours', 8, 2)->nullable();
            $table->string('shift_period')->nullable();
            $table->string('base_salary')->nullable();
            $table->text('benefits_comments')->nullable();
            $table->integer('nombre_postes')->default(1);
            $table->string('location')->nullable();
            $table->json('locations_json')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('apply_instructions')->nullable();
            $table->boolean('is_postulable')->default(false);
            $table->date('start_date')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });

        // Pivot Tables
        Schema::create('job_offer_skill', function (Blueprint $table) {
            $table->foreignId('job_offer_id')->constrained('job_offers')->onDelete('cascade');
            $table->foreignId('skill_id')->constrained('skills')->onDelete('cascade');
            $table->boolean('is_required')->default(false);
            $table->primary(['job_offer_id', 'skill_id']);
        });

        Schema::create('job_offer_language', function (Blueprint $table) {
            $table->foreignId('job_offer_id')->constrained('job_offers')->onDelete('cascade');
            $table->foreignId('language_id')->constrained('languages')->onDelete('cascade');
            $table->string('level')->nullable();
            $table->boolean('is_required')->default(true);
            $table->primary(['job_offer_id', 'language_id']);
        });

        Schema::create('job_offer_permit', function (Blueprint $table) {
            $table->foreignId('job_offer_id')->constrained('job_offers')->onDelete('cascade');
            $table->foreignId('permit_id')->constrained('permits')->onDelete('cascade');
            $table->boolean('is_required')->default(true);
            $table->primary(['job_offer_id', 'permit_id']);
        });

        Schema::create('job_offer_benefit', function (Blueprint $table) {
            $table->foreignId('job_offer_id')->constrained('job_offers')->onDelete('cascade');
            $table->foreignId('benefit_id')->constrained('benefits')->onDelete('cascade');
            $table->primary(['job_offer_id', 'benefit_id']);
        });

        Schema::create('job_offer_sector', function (Blueprint $table) {
            $table->foreignId('job_offer_id')->constrained('job_offers')->onDelete('cascade');
            $table->foreignId('sector_id')->constrained('sectors')->onDelete('cascade');
            $table->primary(['job_offer_id', 'sector_id']);
        });

        // Remarque: job_study renommé en job_offer_study
        Schema::create('job_offer_study', function (Blueprint $table) {
            $table->foreignId('job_offer_id')->constrained('job_offers')->onDelete('cascade');
            $table->foreignId('study_id')->constrained('studies')->onDelete('cascade');
            $table->primary(['job_offer_id', 'study_id']);
        });

        Schema::create('job_offer_experience', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_offer_id')->constrained('job_offers')->onDelete('cascade');
            $table->foreignId('metier_id')->constrained('metiers')->onDelete('cascade');
            $table->boolean('is_required')->default(false);
            $table->string('experience_label')->nullable();
            $table->timestamps();
        });

        // User Pivots
        Schema::create('user_skill', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('skill_id')->constrained('skills')->onDelete('cascade');
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'expert'])->nullable();
            $table->primary(['user_id', 'skill_id']);
            $table->timestamps();
        });

        Schema::create('user_language', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('language_id')->constrained('languages')->onDelete('cascade');
            $table->string('level')->nullable();
            $table->primary(['user_id', 'language_id']);
            $table->timestamps();
        });

        Schema::create('user_permit', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permit_id')->constrained('permits')->onDelete('cascade');
            $table->primary(['user_id', 'permit_id']);
            $table->timestamps();
        });

        // Matching Table
        Schema::create('user_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_offer_id')->constrained('job_offers')->onDelete('cascade');
            $table->integer('pre_score')->nullable();
            $table->integer('ai_score')->nullable();
            $table->integer('final_score')->nullable();
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('ai_raw_response')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->unique(['user_id', 'job_offer_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_matches');
        Schema::dropIfExists('user_permit');
        Schema::dropIfExists('user_language');
        Schema::dropIfExists('user_skill');
        Schema::dropIfExists('job_offer_experience');
        Schema::dropIfExists('job_offer_study');
        Schema::dropIfExists('job_offer_sector');
        Schema::dropIfExists('job_offer_benefit');
        Schema::dropIfExists('job_offer_permit');
        Schema::dropIfExists('job_offer_language');
        Schema::dropIfExists('job_offer_skill');
        Schema::dropIfExists('job_offers');
        Schema::dropIfExists('studies');
        Schema::dropIfExists('permits');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('benefits');
        Schema::dropIfExists('sources');
        Schema::dropIfExists('employers');
        Schema::dropIfExists('sectors');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('metiers');
    }
};
