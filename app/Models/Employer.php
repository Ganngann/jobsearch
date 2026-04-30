<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employer extends Model
{
    protected $fillable = [
        'id_forem',
        'label',
        'logo_uuid',
        'logo_base64',
        'logo_mime_type',
        'description'
    ];

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }
}
