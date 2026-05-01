<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFact extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'content',
        'proposed_content',
        'category',
        'proposed_category',
        'proposed_action',
        'status',
        'is_locked',
        'confidence_score',
        'source_metadata'
    ];

    protected $casts = [
        'source_metadata' => 'array',
        'confidence_score' => 'float',
        'is_locked' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
