<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class RegisterOtpController extends Controller
{
    public function requestOtp(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'recovery_email' => [
                'required',
                'email',
                'unique:users,recovery_email',
                'different:email',
            ],
            'jabatan'  => 'required|string|max:50',
            'password' => [
                'required',
                'confirmed',
                Rules\Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ], [
            'recovery_email.required' => 'Email pemulihan wajib diisi.',
            'recovery_email.email'    => 'Format email pemulihan tidak valid.',
            'recovery_email.unique'   => 'Email pemulihan sudah digunakan oleh akun lain.',
            'recovery_email.different'=> 'Email pemulihan harus berbeda dari email akun utama.',
            'password.min'            => 'Password minimal 8 karakter.',
            'password.mixed'          => 'Password harus mengandung minimal 1 huruf kapital dan 1 huruf kecil.',
            'password.letters'        => 'Password harus mengandung minimal 1 huruf.',
            'password.numbers'        => 'Password harus mengandung minimal 1 angka.',
            'password.symbols'        => 'Password harus mengandung minimal 1 simbol (contoh: !, @, #, $).',
            'password.uncompromised'  => 'Password ini terlalu umum atau pernah bocor. Gunakan password lain.',
        ]);

        // Generate OTP sebelum transaksi agar bisa dikirim setelah commit
        $otp = random_int(100000, 999999);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'           => $request->name,
                'email'          => strtolower($request->email),
                'recovery_email' => strtolower($request->recovery_email),
                'password'       => Hash::make($request->password),
                'jabatan'        => $request->jabatan,
                'is_active'      => false,
            ]);

            DB::table('email_otps')->updateOrInsert(
                ['email' => $user->email],
                [
                    'otp_hash'             => Hash::make($otp),
                    'expires_at'           => now()->addMinutes(10),
                    'attempts'             => 0,
                    'consecutive_failures' => 0,
                    'locked_until'         => null,
                    'last_failed_at'       => null,
                    'verified_at'          => null,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            abort(500, 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
        }

        // Kirim email DI LUAR transaksi — agar kegagalan SMTP tidak membatalkan
        // data yang sudah tersimpan dan tidak memblokir user lanjut ke step OTP.
        $mailWarning = null;
        try {
            Mail::raw(
                "Kode OTP Anda: {$otp}\n\nKode ini berlaku selama 10 menit.\nJangan bagikan kode ini kepada siapapun.",
                function ($m) use ($user) {
                    $m->to($user->email)->subject('Kode OTP Verifikasi Registrasi - Kelurahan Fatubesi');
                }
            );
        } catch (\Throwable $e) {
            // Log tapi jangan gagalkan request — user tetap bisa ke step 2 dan klik "Kirim ulang OTP"
            report($e);
            $mailWarning = 'Email OTP mungkin terlambat sampai. Jika tidak menerima dalam beberapa menit, gunakan tombol "Kirim ulang OTP".';
        }

        return back()->with([
            'otp_sent'     => true,
            'mail_warning' => $mailWarning,
        ]);
    }

   public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|digits:6',
    ]);

    $email = strtolower(trim($request->email));

    $otp = DB::table('email_otps')->where('email', $email)->first();

    if (! $otp || now()->gt($otp->expires_at)) {
        abort(422, 'OTP kadaluarsa atau tidak ditemukan. Silakan minta OTP baru.');
    }

    // SECURITY: Check cooldown/lockout untuk mencegah brute force
    if ($otp->locked_until && now()->lt($otp->locked_until)) {
        $remainingSeconds = now()->diffInSeconds($otp->locked_until);
        $remainingMinutes = ceil($remainingSeconds / 60);
        abort(429, "Terlalu banyak percobaan gagal. Silakan coba lagi dalam {$remainingMinutes} menit.");
    }

    // SECURITY: Check attempt counter untuk mencegah brute force
    if ($otp->attempts >= 5) {
        abort(422, 'Terlalu banyak percobaan gagal. Silakan minta OTP baru.');
    }

    // Increment attempts sebelum validasi
    DB::table('email_otps')->where('email', $email)
        ->increment('attempts');

    if (! Hash::check($request->otp, $otp->otp_hash)) {
        // SECURITY: Progressive Cooldown
        // Increment consecutive failures dan hitung cooldown
        $consecutiveFailures = $otp->consecutive_failures + 1;
        
        // Progressive cooldown: 2 gagal = 1 min, 3 gagal = 5 min, 4+ gagal = 15 min
        $cooldownMinutes = match(true) {
            $consecutiveFailures >= 4 => 15,
            $consecutiveFailures === 3 => 5,
            $consecutiveFailures === 2 => 1,
            default => 0
        };

        $updateData = [
            'consecutive_failures' => $consecutiveFailures,
            'last_failed_at' => now(),
        ];

        if ($cooldownMinutes > 0) {
            $updateData['locked_until'] = now()->addMinutes($cooldownMinutes);
        }

        DB::table('email_otps')->where('email', $email)->update($updateData);

        $message = 'Kode OTP salah. Sisa percobaan: ' . (5 - ($otp->attempts + 1));
        if ($cooldownMinutes > 0) {
            $message .= " Akun dikunci selama {$cooldownMinutes} menit.";
        }
        
        abort(422, $message);
    }

    // SECURITY: Reset consecutive failures saat berhasil
    DB::table('email_otps')->where('email', $email)->update([
        'consecutive_failures' => 0,
        'locked_until' => null,
        'last_failed_at' => null,
    ]);

    /** 🔥 BUAT USER DI SINI (BUKAN DI requestOtp) */
    $user = User::where('email', $email)->firstOrFail();

    // CREDENTIAL: Ambil credential tetap berdasarkan jabatan dari config
    $jabatanKey = strtolower($user->jabatan);
    $credentials = config('credentials.jabatan_credentials', []);
    $credential = $credentials[$jabatanKey] ?? config('credentials.default_credential', 'A-999');

    DB::transaction(function () use ($user, $credential, $email) {
        // Update user dengan credential tetap berdasarkan jabatan
        $user->forceFill([
            'is_active' => true,
            'email_verified_at' => now(),
            'credential_code_hash' => Hash::make($credential),
            'credential_issued_at' => now(),
        ])->save();

        // Tandai OTP sebagai terverifikasi
        DB::table('email_otps')
            ->where('email', $email)
            ->update(['verified_at' => now()]);
    });

    return Inertia::render('Auth/RegisterSuccess', [
        'credential' => $credential,
        'email' => $user->email,
    ]);
}
}