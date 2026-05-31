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
        'name', 'nip', 'email', 'recovery_email', 'password', 'jabatan',
        'role', 'is_active', 'email_verified_at',
        'credential_code_hash', 'credential_issued_at',
        // SECURITY (A07): Persistent brute-force lockout fields
        'failed_login_attempts', 'locked_until', 'last_failed_login_at',
    ];

    // Field sensitif — tidak pernah dikembalikan dalam JSON/API response
    protected $hidden = [
        'password',
        'remember_token',
        'credential_code_hash',  // hash kode kredensial jangan bocor
    ];

    protected $casts = [
        'email_verified_at'      => 'datetime',
        'credential_issued_at'   => 'datetime',
        'is_active'              => 'boolean',
        // SECURITY (A07): Cast lockout timestamp
        'locked_until'           => 'datetime',
        'last_failed_login_at'   => 'datetime',
    ];

    
    const MAX_LOGIN_ATTEMPTS = 10;
    
    const LOCKOUT_DURATION_MINUTES = 15;

   
    public function isLockedOut(): bool
    {
        return $this->locked_until !== null && now()->lt($this->locked_until);
    }

    /**
     * Record a failed login attempt and lock account if threshold is reached.
     * Returns true if the account has just been locked.
     */
    public function recordFailedLogin(): bool
    {
        $this->increment('failed_login_attempts');
        $this->last_failed_login_at = now();

        if ($this->failed_login_attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $this->locked_until = now()->addMinutes(self::LOCKOUT_DURATION_MINUTES);
            $this->save();
            return true; // account just locked
        }

        $this->save();
        return false;
    }

    /**
     * Reset lockout state after a successful login.
     */
    public function clearLoginAttempts(): void
    {
        $this->failed_login_attempts = 0;
        $this->locked_until          = null;
        $this->last_failed_login_at  = null;
        $this->save();
    }
}
