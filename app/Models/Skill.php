<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = ['code', 'label', 'type', 'slug'];

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

    public function userFacts(): BelongsToMany
    {
        return $this->belongsToMany(UserFact::class, 'fact_skill', 'skill_id', 'user_fact_id');
    }
}
