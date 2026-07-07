<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letter extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'template_slug',
        'title',
        'no_surat',
        'index_code',
        'urut',
        'month_roman',
        'year',
        'payload',
        'printed_at',
        'printed_by',
        'signer',
        'is_manual',
    ];

    protected $hidden = [
        'payload',
        'deleted_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'signer' => 'array',
        'printed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function printedBy()
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    public function dispositions()
    {
        return $this->hasMany(LetterDisposition::class);
    }

    public function documents()
    {
        return $this->hasMany(LetterDocument::class);
    }
}
