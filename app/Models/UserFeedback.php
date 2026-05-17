<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFeedback extends Model
{
    protected $table = 'user_feedback';

    protected $fillable = ['user_id', 'message', 'page_url', 'type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
