<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'payload' => 'array',
        'printed_at' => 'datetime',
    ];
}
