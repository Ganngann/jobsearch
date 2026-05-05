<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'headline',
        'profile_text',
        'aspirations',
        'location',
        'zip_code',
        'radius',
        'phone',
        'avatar',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'birth_date',
        'availability_status',
        'links',
        'daily_ai_limit',
        'daily_ai_usage',
        'last_seen_at',
        'last_ai_usage_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'links' => 'array',
            'birth_date' => 'date',
            'last_seen_at' => 'datetime',
            'last_ai_usage_at' => 'datetime',
        ];
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skill')
            ->withPivot('level', 'status')
            ->withTimestamps();
    }

    public function validatedSkills(): BelongsToMany
    {
        return $this->skills()->wherePivot('status', 'active');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'user_language')
            ->withPivot('level')
            ->withTimestamps();
    }

    public function permits(): BelongsToMany
    {
        return $this->belongsToMany(Permit::class, 'user_permit')
            ->withTimestamps();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(UserMatch::class);
    }

    public function facts(): HasMany
    {
        return $this->hasMany(UserFact::class);
    }

    public function profileMessages(): HasMany
    {
        return $this->hasMany(ProfileMessage::class)->orderBy('created_at', 'asc');
    }

    public function profileSessions(): HasMany
    {
        return $this->hasMany(ProfileSession::class)->orderBy('updated_at', 'desc');
    }


    public function preferredMetiers(): BelongsToMany
    {
        return $this->belongsToMany(Metier::class, 'user_metier')->withPivot('status')->withTimestamps();
    }


    public function preferredReferentielMetiers(): BelongsToMany
    {
        return $this->belongsToMany(ReferentielMetier::class, 'user_preferred_referentiel')->withPivot('status')->withTimestamps();
    }

    public function discoverySuggestions()
    {
        return $this->hasMany(DiscoverySuggestion::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class)->orderBy('start_date', 'desc');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class)->orderBy('graduation_year', 'desc');
    }


    public function projects(): HasMany
    {
        return $this->hasMany(Project::class)->orderBy('start_date', 'desc');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class)->orderBy('issue_date', 'desc');
    }

    public function interests(): HasMany
    {
        return $this->hasMany(Interest::class);
    }

    public function volunteerExperiences(): HasMany
    {
        return $this->hasMany(VolunteerExperience::class)->orderBy('start_date', 'desc');
    }

    public function getNarrativeProgress(): int
    {
        $factsCount = $this->facts()->count();
        $journeyCount = $this->experiences()->count() + $this->educations()->count();
        
        $narrativeScore = min(70, ($factsCount / 20) * 70);
        $journeyScore = min(30, ($journeyCount / 3) * 30);
        
        return (int) round($narrativeScore + $journeyScore);
    }

    public function getSkillsProgress(): int
    {
        $skillsCount = $this->validatedSkills()->count();
        return (int) min(100, round(($skillsCount / 50) * 100));
    }

    public function getRomeProgress(): int
    {
        $specificCount = $this->preferredMetiers()->wherePivot('status', 'favorite')->count();
        $familyCount = $this->preferredReferentielMetiers()->wherePivot('status', 'favorite')->count();
        $romeCount = $specificCount + $familyCount;
        
        return (int) min(100, round(($romeCount / 3) * 100));
    }

    public function getMobilityProgress(): int
    {
        return $this->zip_code ? 100 : 0;
    }

    /**
     * Vérifie si le profil est prêt pour le matching.
     */
    public function isProfileMature(): bool
    {
        return $this->getNarrativeProgress() >= 100 
            && $this->getSkillsProgress() >= 100 
            && $this->getRomeProgress() >= 100 
            && $this->getMobilityProgress() >= 100;
    }

    /**
     * Retourne la liste des éléments manquants pour le matching.
     */
    public function getMissingProfileElements(): array
    {
        $missing = [];

        if ($this->getNarrativeProgress() < 100) {
            $missing[] = 'un récit complet (faits et expériences)';
        }

        if ($this->getSkillsProgress() < 100) {
            $missing[] = 'au moins 50 compétences validées';
        }

        if ($this->getRomeProgress() < 100) {
            $missing[] = 'au moins 3 métiers favoris';
        }

        if ($this->getMobilityProgress() < 100) {
            $missing[] = 'votre zone de mobilité (code postal)';
        }

        return $missing;
    }

    /**
     * Vérifie si l'utilisateur est actuellement considéré comme en ligne (activité < 15 min).
     */
    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->diffInMinutes(now()) < 15;
    }

    /**
     * Tente de consommer un point de quota IA. Retourne true si réussi.
     */
    public function useAiPoint(): bool
    {
        // Reset du compteur si on a changé de jour
        if ($this->last_ai_usage_at && !$this->last_ai_usage_at->isToday()) {
            $this->daily_ai_usage = 0;
        }

        if ($this->daily_ai_usage >= $this->daily_ai_limit) {
            return false;
        }

        $this->increment('daily_ai_usage');
        $this->update(['last_ai_usage_at' => now()]);

        return true;
    }
}
