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
        'pre_score_details',
        'ai_score',
        'final_score',
        'strengths',
        'weaknesses',
        'ai_analysis_narrative',
        'ai_recommendation',
        'ai_raw_response',
        'ai_status',
        'analyzed_at',
        'ai_at_pre_score',
        'vector_score',
    ];

    protected $casts = [
        'pre_score_details' => 'array',
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
