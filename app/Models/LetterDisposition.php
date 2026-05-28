<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterDisposition extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_id',
        'from_user_id',
        'to_user_id',
        'catatan',
        'status',
    ];

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
