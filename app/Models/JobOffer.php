<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOffer extends Model
{
    /**
     * Utilise l'ID du Forem comme identifiant dans les URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'forem_id';
    }

    protected $fillable = [
        'forem_id',
        'forem_ref',
        'title',
        'metier_id',
        'employer_id',
        'source_id',
        'description',
        'contract_type',
        'working_regime',
        'working_regime_detail',
        'working_hours',
        'shift_period',
        'base_salary',
        'benefits_comments',
        'nombre_postes',
        'location',
        'locations_json',
        'contact_name',
        'contact_email',
        'contact_phone',
        'apply_instructions',
        'apply_url',
        'is_postulable',
        'start_date',
        'published_at',
        'expires_at',
        'raw_data',
        'is_detailed',
        'status',
        'last_seen_at',
        'detailed_at',
        'content_hash',
    ];

    protected $casts = [
        'locations_json' => 'array',
        'raw_data' => 'array',
        'is_postulable' => 'boolean',
        'is_detailed' => 'boolean',
        'start_date' => 'date',
        'published_at' => 'datetime',
        'expires_at' => 'date',
        'working_hours' => 'decimal:2',
        'last_seen_at' => 'datetime',
        'detailed_at' => 'datetime',
    ];

    public function metier(): BelongsTo
    {
        return $this->belongsTo(Metier::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_offer_skill')
            ->withPivot('is_required');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'job_offer_language')
            ->withPivot(['level', 'is_required']);
    }

    public function permits(): BelongsToMany
    {
        return $this->belongsToMany(Permit::class, 'job_offer_permit')
            ->withPivot('is_required');
    }

    public function benefits(): BelongsToMany
    {
        return $this->belongsToMany(Benefit::class, 'job_offer_benefit');
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'job_offer_sector');
    }

    public function studies(): BelongsToMany
    {
        return $this->belongsToMany(Study::class, 'job_offer_study');
    }

    public function requiredExperiences(): BelongsToMany
    {
        return $this->belongsToMany(Metier::class, 'job_offer_experience')
            ->withPivot(['is_required', 'experience_label'])
            ->withTimestamps();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(UserMatch::class);
    }

    /**
     * Retourne le match de l'utilisateur connecté.
     */
    public function userMatch()
    {
        return $this->hasOne(UserMatch::class)->where('user_id', auth()->id());
    }
}
