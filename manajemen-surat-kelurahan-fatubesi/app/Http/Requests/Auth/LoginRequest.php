<?php

namespace App\Http\Requests\Auth;

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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'credential_code' => ['required', 'regex:/^[A-Z0-9]{1,10}-\d{3,6}$/i'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
{
    $this->ensureIsNotRateLimited();

    $email = strtolower(trim($this->input('email')));
    $credential = strtoupper(trim($this->input('credential_code')));
    $password = (string) $this->input('password');

    $user = \App\Models\User::where('email', $email)->first();

    if (! $user) {
        $this->throwFailedAuthentication();
    }

    // ✅ wajib verified & aktif (opsional)
    if (! $user->is_active) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => 'Akun belum diverifikasi.',
        ]);
    }

    // ✅ cek credential
    if (! $user->credential_code_hash || ! \Illuminate\Support\Facades\Hash::check($credential, $user->credential_code_hash)) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'credential_code' => 'Credential tidak sesuai.',
        ]);
    }

    // ✅ cek password manual agar pesan error jelas
    if (! \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'password' => 'Password tidak sesuai.',
        ]);
    }

    // ✅ login
    \Illuminate\Support\Facades\Auth::login($user, $this->boolean('remember'));

    \Illuminate\Support\Facades\RateLimiter::clear($this->throttleKey());
}

protected function throwFailedAuthentication(): void
{
    \Illuminate\Support\Facades\RateLimiter::hit($this->throttleKey());

    throw \Illuminate\Validation\ValidationException::withMessages([
        'email' => trans('auth.failed'),
    ]);
}
    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
