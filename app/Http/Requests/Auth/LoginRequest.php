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
            'name'            => ['required', 'string'],
            'password'        => ['required', 'string'],
            // credential_code bersifat opsional — tidak diperlukan untuk login admin
            'credential_code' => ['nullable', 'string'],
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

        $name       = trim($this->input('name'));
        $credential = strtoupper(trim($this->input('credential_code')));
        $password   = (string) $this->input('password');

        $user = \App\Models\User::where('name', $name)->first();

        if (! $user) {
            $this->throwFailedAuthentication();
        }

        if (! $user->is_active) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => 'Akun belum diverifikasi.',
            ]);
        }

        // Admin tidak memerlukan credential_code — cukup username + password
        if ($user->role !== 'admin') {
            $credential = strtoupper(trim($this->input('credential_code') ?? ''));
            if (! $credential || ! $user->credential_code_hash || ! \Illuminate\Support\Facades\Hash::check($credential, $user->credential_code_hash)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'credential_code' => 'Credential tidak sesuai.',
                ]);
            }
        }

        if (! \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'password' => 'Password tidak sesuai.',
            ]);
        }

        \Illuminate\Support\Facades\Auth::login($user, $this->boolean('remember'));

        \Illuminate\Support\Facades\RateLimiter::clear($this->throttleKey());
    }

    protected function throwFailedAuthentication(): void
    {
        \Illuminate\Support\Facades\RateLimiter::hit($this->throttleKey());

        throw \Illuminate\Validation\ValidationException::withMessages([
            'name' => trans('auth.failed'),
        ]);
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

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
