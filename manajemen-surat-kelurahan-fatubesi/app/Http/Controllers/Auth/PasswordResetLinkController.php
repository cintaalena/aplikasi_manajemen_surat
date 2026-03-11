<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * Alur:
     * 1. User memasukkan nama (username) dan email pemulihan
     * 2. Sistem mencari akun yang cocok keduanya
     * 3. Token reset dibuat dan dikirim ke recovery_email
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'           => 'required|string',
            'recovery_email' => 'required|email',
        ], [
            'name.required'           => 'Username wajib diisi.',
            'recovery_email.required' => 'Email pemulihan wajib diisi.',
            'recovery_email.email'    => 'Format email pemulihan tidak valid.',
        ]);

        $user = User::where('name', trim($request->name))
            ->where('recovery_email', strtolower(trim($request->recovery_email)))
            ->where('is_active', true)
            ->first();

        // Pesan error generik — tidak membocorkan field mana yang salah
        if (! $user) {
            return back()->withErrors([
                'recovery_email' => 'Username atau email pemulihan tidak sesuai.',
            ]);
        }

        // Buat token dan simpan di password_reset_tokens dengan email AKUN sebagai key
        $token = Password::broker()->getRepository()->create($user);

        // Bangun URL reset; email di URL adalah email AKUN (untuk verifikasi token)
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        // Kirim ke RECOVERY EMAIL — bukan email akun
        Mail::raw(
            "Halo {$user->name},\n\n" .
            "Kami menerima permintaan reset password untuk akun Anda di Sistem Kelurahan Fatubesi.\n\n" .
            "Klik link berikut untuk mengatur password baru (berlaku 60 menit):\n\n" .
            "{$resetUrl}\n\n" .
            "Jika Anda tidak meminta reset password, abaikan email ini. Password Anda tidak akan berubah.",
            function ($m) use ($user) {
                $m->to($user->recovery_email)
                  ->subject('Reset Password - Sistem Kelurahan Fatubesi');
            }
        );

        return back()->with(
            'status',
            'Jika email pemulihan Anda terdaftar, link reset password telah dikirim.'
        );
    }
}
