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
}
