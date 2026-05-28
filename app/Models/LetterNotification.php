<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'letter_id',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }
}
