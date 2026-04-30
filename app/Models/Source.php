<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    protected $fillable = ['code', 'label'];

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }
}
