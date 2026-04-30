<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permit extends Model
{
    protected $fillable = ['code', 'label', 'value'];

    public function jobOffers(): BelongsToMany
    {
        return $this->belongsToMany(JobOffer::class, 'job_offer_permit')
            ->withPivot('is_required');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permit')
            ->withTimestamps();
    }
}
