<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Auditable;

    protected $fillable = [
        'name', 'email', 'recovery_email', 'password', 'jabatan',
        'is_active', 'email_verified_at',
        'credential_code_hash', 'credential_issued_at',
    ];

    // Field sensitif — tidak pernah dikembalikan dalam JSON/API response
    protected $hidden = [
        'password',
        'remember_token',
        'credential_code_hash',  // hash kode kredensial jangan bocor
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'credential_issued_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
