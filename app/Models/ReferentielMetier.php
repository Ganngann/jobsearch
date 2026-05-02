<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferentielMetier extends Model
{
    protected $table = 'referentiel_metiers';

    protected $fillable = [
        'code',
        'title',
        'description',
        'family_name',
        'is_active',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_preferred_referentiel');
    }

    public function getRouteKeyName()
    {
        return 'code';
    }
}
