<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterCounter extends Model
{
    use HasFactory;

    protected $fillable = ['template_slug', 'count'];
}
