<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'letter_id',
        'user_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
