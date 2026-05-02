<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Metier extends Model
{
    protected $fillable = ['code', 'guid', 'label'];

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }

    public function experiencedInJobOffers(): BelongsToMany
    {
        return $this->belongsToMany(JobOffer::class, 'job_offer_experience')
            ->withPivot(['is_required', 'experience_label'])
            ->withTimestamps();
    }

    public function preferredByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_metier')->withTimestamps();
    }

    public function blacklistedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blacklisted_metiers')->withTimestamps();
    }
}
