<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerExperience extends Model
{
    protected $fillable = [
        'user_id',
        'organization',
        'role',
        'description',
        'start_date',
        'end_date',
        'status',
        'proposed_action',
        'proposed_data'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'proposed_data' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
