<?php

namespace App\Http\Requests\Auth;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'            => ['required', 'string'],
            'password'        => ['required', 'string'],
            'credential_code' => ['nullable', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * SECURITY (A07): Multi-layer brute force protection:
     *   1. Per-minute rate limiting (RateLimiter — resets every minute)
     *   2. Persistent account lockout (DB-tracked — survives rate limit reset)
     *   3. Generic error messages to prevent username enumeration
     *   4. Timing-safe comparison via Hash::check (constant-time)
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    //ini fungsi autentikasi login
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $name     = trim($this->input('name'));
        $password = (string) $this->input('password');

        $user = User::where('name', $name)->first();

        if (! $user) {
            Hash::check($password, '$2y$10$dummyhashtopreventtimingattacks..............');
            $this->recordFailedAttempt(null, $name);
            $this->throwFailedAuthentication();
        }

        if ($user->isLockedOut()) {
            SecurityEvent::record(
                SecurityEvent::EVENT_ACCOUNT_LOCKED,
                SecurityEvent::SEVERITY_WARNING,
                $user->id,
                $name,
                ['locked_until' => $user->locked_until?->toIso8601String()]
            );

            $remaining = now()->diffInMinutes($user->locked_until, false);
            throw ValidationException::withMessages([
                'name' => "Akun dikunci sementara akibat terlalu banyak percobaan login. "
                        . "Coba lagi dalam {$remaining} menit.",
            ]);
        }

        $credentialOk  = true;
        $passwordOk    = Hash::check($password, $user->password);

        if ($user->role !== 'admin') {
            $credential = strtoupper(trim($this->input('credential_code') ?? ''));
            $credentialOk = $credential !== ''
                && $user->credential_code_hash !== null
                && Hash::check($credential, $user->credential_code_hash);
        }

        if (! $passwordOk || ! $credentialOk || ! $user->is_active) {
            $justLocked = $user->recordFailedLogin();
            $this->recordFailedAttempt($user->id, $name);

            if ($justLocked) {
                SecurityEvent::record(
                    SecurityEvent::EVENT_ACCOUNT_LOCKED,
                    SecurityEvent::SEVERITY_CRITICAL,
                    $user->id,
                    $name,
                    ['attempts' => $user->failed_login_attempts]
                );
            }

            $this->throwFailedAuthentication();
        }

        Auth::login($user, $this->boolean('remember'));
        RateLimiter::clear($this->throttleKey());

        $user->clearLoginAttempts();

        SecurityEvent::record(
            SecurityEvent::EVENT_LOGIN_SUCCESS,
            SecurityEvent::SEVERITY_INFO,
            $user->id,
            $name
        );
    }

    /**
     * SECURITY (A09): Log a failed login attempt to the security events table.
     */
    private function recordFailedAttempt(?int $userId, string $usernameAttempted): void
    {
        SecurityEvent::record(
            SecurityEvent::EVENT_LOGIN_FAILED,
            SecurityEvent::SEVERITY_WARNING,
            $userId,
            $usernameAttempted
        );
    }

    /**
     * Throw generic authentication failure.
     * SECURITY (A07): Single generic message — do not reveal whether user exists.
     */
    protected function throwFailedAuthentication(): void
    {
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'name' => trans('auth.failed'),
        ]);
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        SecurityEvent::record(
            SecurityEvent::EVENT_RATE_LIMIT_EXCEEDED,
            SecurityEvent::SEVERITY_WARNING,
            null,
            $this->input('name'),
            ['throttle_key' => $this->throttleKey()]
        );

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'name' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('name')).'|'.$this->ip());
    }
}

