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

            DB::table('email_otps')->updateOrInsert(
                ['email' => $user->email],
                [
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(10),
                    'attempts' => 0,
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
        abort(422, 'OTP kadaluarsa');
    }

    if (! Hash::check($request->otp, $otp->otp_hash)) {
        abort(422, 'OTP salah');
    }

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