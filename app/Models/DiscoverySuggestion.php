<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscoverySuggestion extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'title',
        'reason',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
