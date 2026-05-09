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
        'profile_published_at',
        'vector_embedding',
        'daily_ai_limits',
        'daily_ai_usage_breakdown',
        'is_admin',
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
            'profile_published_at' => 'datetime',
            'vector_embedding' => 'array',
            'daily_ai_limits' => 'array',
            'daily_ai_usage_breakdown' => 'array',
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
     * Calcul global de complétion du profil (Moyenne des 4 piliers).
     */
    public function getProfileCompletionAttribute(): int
    {
        $narrative = $this->getNarrativeProgress();
        $skills = $this->getSkillsProgress();
        $rome = $this->getRomeProgress();
        $mobility = $this->getMobilityProgress();
        
        return (int) round(($narrative + $skills + $rome + $mobility) / 4);
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
     * Tente de consommer un point de quota IA pour un modèle spécifique.
     * Si aucune limite spécifique n'est définie pour le modèle, on utilise la limite globale.
     */
    public function useAiPoint(?string $model = null): bool
    {
        // 1. Reset global et breakdown si on a changé de jour
        if ($this->last_ai_usage_at && !$this->last_ai_usage_at->isToday()) {
            $this->update([
                'daily_ai_usage' => 0,
                'daily_ai_usage_breakdown' => [],
                'last_ai_usage_at' => now()
            ]);
        }

        // 2. Identification de la limite applicable
        $limit = $this->daily_ai_limit;
        if ($model) {
            if (isset($this->daily_ai_limits[$model])) {
                $limit = $this->daily_ai_limits[$model];
            } else {
                // Fallback sur une limite globale par modèle définie dans les settings, sinon limite user globale
                $limit = \App\Models\Setting::get("limit_{$model}", $limit);
            }
        }

        // 3. Vérification de l'usage pour ce modèle (ou global si pas de modèle)
        $currentUsage = $model ? ($this->daily_ai_usage_breakdown[$model] ?? 0) : $this->daily_ai_usage;

        if ($currentUsage >= $limit) {
            \Illuminate\Support\Facades\Log::warning("User #{$this->id} reached AI limit for model {$model} ({$currentUsage}/{$limit})");
            return false;
        }

        // 4. Incrémentation
        if ($model) {
            $breakdown = $this->daily_ai_usage_breakdown ?? [];
            $breakdown[$model] = ($breakdown[$model] ?? 0) + 1;
            
            $this->update([
                'daily_ai_usage' => $this->daily_ai_usage + 1,
                'daily_ai_usage_breakdown' => $breakdown,
                'last_ai_usage_at' => now()
            ]);
        } else {
            $this->increment('daily_ai_usage', 1, [
                'last_ai_usage_at' => now()
            ]);
        }

        \Illuminate\Support\Facades\Log::info("User #{$this->id} AI Point consumed for model " . ($model ?? 'global') . ". New usage: " . ($model ? $this->daily_ai_usage_breakdown[$model] : $this->daily_ai_usage));

        return true;
    }

    /**
     * Retourne le nombre de points IA restants pour un modèle donné (ou global).
     */
    public function getAiRemainingPoints(?string $model = null): int
    {
        // Si les points n'ont pas encore été réinitialisés pour aujourd'hui
        if ($this->last_ai_usage_at && !$this->last_ai_usage_at->isToday()) {
            if ($model) {
                if (isset($this->daily_ai_limits[$model])) {
                    return $this->daily_ai_limits[$model];
                }
                return (int) \App\Models\Setting::get("limit_{$model}", $this->daily_ai_limit);
            }
            return $this->daily_ai_limit;
        }

        $limit = $this->daily_ai_limit;
        if ($model) {
            if (isset($this->daily_ai_limits[$model])) {
                $limit = $this->daily_ai_limits[$model];
            } else {
                $limit = (int) \App\Models\Setting::get("limit_{$model}", $limit);
            }
        }

        $currentUsage = $model ? ($this->daily_ai_usage_breakdown[$model] ?? 0) : $this->daily_ai_usage;
        
        return max(0, $limit - $currentUsage);
    }

    /**
     * Retourne la date de la dernière modification substantielle du profil.
     * Englobe le modèle User et toutes ses relations critiques (skills, facts, experiences...).
     */
    public function profileUpdatedAt(): \Illuminate\Support\Carbon
    {
        $timestamps = [
            $this->updated_at,
            $this->skills()->max('user_skill.updated_at'),
            $this->languages()->max('user_language.updated_at'),
            $this->facts()->max('updated_at'),
            $this->experiences()->max('updated_at'),
            $this->educations()->max('updated_at'),
            $this->projects()->max('updated_at'),
            $this->certifications()->max('updated_at'),
            $this->volunteerExperiences()->max('updated_at'),
        ];

        $maxTimestamp = collect($timestamps)->filter()->max();

        return $maxTimestamp ? \Illuminate\Support\Carbon::parse($maxTimestamp) : $this->updated_at;
    }

    /**
     * Détermine si le profil contient des modifications non publiées.
     */
    public function isProfileDirty(): bool
    {
        if (!$this->profile_published_at) {
            return true;
        }

        return $this->profileUpdatedAt()->gt($this->profile_published_at);
    }

    public function aiLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AiLog::class);
    }
}
