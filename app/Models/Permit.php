<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permit extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $fillable = ['code', 'label', 'value', 'slug'];

    /**
     * Génère un slug normalisé pour les permis (ex: "catégorie B" -> "b")
     */
    public static function generateSlug($label): string
    {
        if (preg_match('/catégorie\s+([A-Z0-9+]+)/i', $label, $matches)) {
            return \Illuminate\Support\Str::slug($matches[1]);
        } 
        
        if (preg_match('/^([A-Z0-9+]+):/i', $label, $matches)) {
            return \Illuminate\Support\Str::slug($matches[1]);
        }

        return \Illuminate\Support\Str::slug($label);
    }

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
