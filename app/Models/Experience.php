<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Experience extends Model
{
    protected $fillable = [
        'user_id',
        'company',
        'company_logo',
        'title',
        'employment_type',
        'description',
        'location',
        'start_date',
        'end_date',
        'is_current',
        'status',
        'proposed_action'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function facts(): HasMany
    {
        return $this->hasMany(UserFact::class);
    }
}
