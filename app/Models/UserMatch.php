<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMatch extends Model
{
    protected $fillable = [
        'user_id',
        'job_offer_id',
        'pre_score',
        'ai_score',
        'final_score',
        'strengths',
        'weaknesses',
        'ai_analysis_narrative',
        'ai_recommendation',
        'ai_raw_response',
        'analyzed_at',
    ];

    protected $casts = [
        'strengths' => 'array',
        'weaknesses' => 'array',
        'ai_raw_response' => 'array',
        'analyzed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobOffer(): BelongsTo
    {
        return $this->belongsTo(JobOffer::class);
    }
}
