<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterDocument extends Model
{
    protected $fillable = [
        'letter_id',
        'doc_key',
        'doc_label',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    protected $appends = ['url'];

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }

    /**
     * URL publik untuk diakses dari browser.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
