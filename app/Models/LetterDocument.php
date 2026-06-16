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
        'uploaded_by',
    ];

    protected $appends = ['url'];

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }

    /**
     * URL untuk mengakses file — stream langsung via PHP route,
     * tidak bergantung pada APP_URL atau symlink Apache.
     */
    public function getUrlAttribute(): string
    {
        return route('surat.dokumen.file', ['document' => $this->id], false);
    }
}
