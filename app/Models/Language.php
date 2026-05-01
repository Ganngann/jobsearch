<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Language extends Model
{
    protected $fillable = ['code', 'label', 'slug'];

    public function jobOffers(): BelongsToMany
    {
        return $this->belongsToMany(JobOffer::class, 'job_offer_language')
            ->withPivot(['level', 'is_required']);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_language')
            ->withPivot('level')
            ->withTimestamps();
    }
}
