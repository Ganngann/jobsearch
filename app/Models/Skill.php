<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = ['code', 'label', 'type'];

    public function jobOffers(): BelongsToMany
    {
        return $this->belongsToMany(JobOffer::class, 'job_offer_skill')
            ->withPivot('is_required');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skill')
            ->withPivot('level')
            ->withTimestamps();
    }
}
