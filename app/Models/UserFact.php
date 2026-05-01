<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFact extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'category',
        'status',
        'confidence_score',
        'source_metadata'
    ];

    protected $casts = [
        'source_metadata' => 'array',
        'confidence_score' => 'float'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
