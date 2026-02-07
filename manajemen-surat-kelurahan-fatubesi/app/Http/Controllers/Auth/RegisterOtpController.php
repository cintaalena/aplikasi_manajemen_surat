<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RegisterOtpController extends Controller
{
    public function requestOtp(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'jabatan' => 'required|string|max:50',
            'password' => 'required|confirmed|min:8',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => strtolower($request->email),
                'password' => Hash::make($request->password),
                'jabatan' => $request->jabatan,
                'is_active' => false,
            ]);

            $otp = random_int(100000, 999999);

            // SECURITY: Reset attempts, cooldown, dan generate OTP baru
            DB::table('email_otps')->updateOrInsert(
                ['email' => $user->email],
                [
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(10),
                    'attempts' => 0, // SECURITY: Reset counter saat OTP baru
                    'consecutive_failures' => 0, // SECURITY: Reset consecutive failures
                    'locked_until' => null, // SECURITY: Reset cooldown
                    'last_failed_at' => null, // SECURITY: Reset last failed timestamp
                    'verified_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            Mail::raw("Kode OTP Anda: {$otp} (berlaku 10 menit)", function ($m) use ($user) {
                $m->to($user->email)->subject('Verifikasi Registrasi Akun');
            });

            DB::commit();

            // opsional: simpan email agar form OTP tidak perlu ketik ulang
            return back()->with([
                'status' => 'OTP dikirim ke email',
                'otp_email' => $user->email,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            abort(500, 'Terjadi kesalahan saat mengirim OTP.');
        }
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

    $map = [
        'lurah' => 'LRH',
        'sekretaris' => 'SEK',
        'kasie pelayanan masyarakat' => 'KPM',
        'kasie pem dan trantib umum' => 'KPT',
        'pengelola pemberdayaan masyarakat dan kelembagaan' => 'PPM',
        'pengadministrasian umum' => 'ADM',
        'pppk' => 'PPPK',
        'ptt' => 'PTT',
    ];

    $jabatanKey = strtolower($user->jabatan);
    $prefix = $map[$jabatanKey] ?? 'USR';

    $credential = DB::transaction(function () use ($user, $jabatanKey, $prefix, $email) {

        $row = DB::table('credential_counters')
            ->where('jabatan_key', $jabatanKey)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            DB::table('credential_counters')->insert([
                'jabatan_key' => $jabatanKey,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $last = 0;
        } else {
            $last = $row->last_number;
        }

        $next = $last + 1;

        DB::table('credential_counters')
            ->where('jabatan_key', $jabatanKey)
            ->update(['last_number' => $next]);

        $credential = sprintf('%s-%03d', $prefix, $next);

        $user->forceFill([
            'is_active' => true,
            'email_verified_at' => now(),
            'credential_code_hash' => Hash::make($credential),
            'credential_issued_at' => now(),
        ])->save();

        DB::table('email_otps')
            ->where('email', $email)
            ->update(['verified_at' => now()]);

        return $credential;
    });

    return Inertia::render('Auth/RegisterSuccess', [
        'credential' => $credential,
        'email' => $user->email,
    ]);
}
}