<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLog extends Model
{
    public $timestamps = false; // Géré manuellement ou via useCurrent()

    protected $fillable = [
        'user_id',
        'model',
        'category',
        'tokens_in',
        'tokens_out',
        'created_at'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
