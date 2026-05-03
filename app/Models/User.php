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
        ];
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skill')
            ->withPivot('level')
            ->withTimestamps();
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

    public function blacklistedSkills(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_blacklisted_skills');
    }

    public function preferredMetiers(): BelongsToMany
    {
        return $this->belongsToMany(Metier::class, 'user_metier')->withTimestamps();
    }

    public function blacklistedMetiers(): BelongsToMany
    {
        return $this->belongsToMany(Metier::class, 'user_blacklisted_metiers')->withTimestamps();
    }

    public function preferredReferentielMetiers(): BelongsToMany
    {
        return $this->belongsToMany(ReferentielMetier::class, 'user_preferred_referentiel')->withTimestamps();
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

    public function blacklistedReferentielMetiers(): BelongsToMany
    {
        return $this->belongsToMany(ReferentielMetier::class, 'user_blacklisted_referentiel')->withTimestamps();
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

    /**
     * Vérifie si le profil est prêt pour le matching.
     */
    public function isProfileMature(): bool
    {
        return empty($this->getMissingProfileElements());
    }

    /**
     * Retourne la liste des éléments manquants pour le matching.
     */
    public function getMissingProfileElements(): array
    {
        $missing = [];

        if ($this->preferredMetiers()->count() < 1) {
            $missing[] = 'un métier préféré';
        }

        if ($this->skills()->count() < 5) {
            $count = 5 - $this->skills()->count();
            $missing[] = "{$count} compétence(s) technique(s) supplémentaire(s)";
        }

        if (empty($this->zip_code)) {
            $missing[] = 'votre code postal (zone de mobilité)';
        }

        return $missing;
    }
}
