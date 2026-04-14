<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LurahUserSeeder extends Seeder
{
    /**
     * Daftar akun yang akan di-seed.
     * Developer cukup menambahkan entri baru di array ini.
     *
     * Format tiap entri:
     * [
     *     'name'       => 'Nama Lengkap',
     *     'email'      => 'email@contoh.com',
     *     'password'   => 'PasswordRahasia123!',
     *     'kode_user'  => 'X-000',
     * ]
     */
    private array $users = [
        [
            'name'            => 'Lurah',
            'email'           => 'emaildummylurah@gmail.com',
            'password'        => 'Lurah1234!',
            'credential_code' => 'A-001',
            'jabatan'         => 'lurah',
        ],

        // ── Tambahkan akun baru di bawah ini ──────────────────────────────
        // [
        //     'name'            => 'Sekretaris',
        //     'email'           => 'sekretaris@kelurahan.com',
        //     'password'        => 'Sekretaris1234!',
        //     'credential_code' => 'A-002',
        //     'jabatan'         => 'sekretaris',
        // ],
        // ──────────────────────────────────────────────────────────────────
    ];

    public function run(): void
    {
        foreach ($this->users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name'                  => $userData['name'],
                    'email'                 => $userData['email'],
                    'password'              => Hash::make($userData['password']),
                    'credential_code_hash'  => Hash::make(strtoupper($userData['credential_code'])),
                    'jabatan'               => $userData['jabatan'],
                    'is_active'             => true,
                    'email_verified_at'     => now(),
                ]
            );
        }
    }
}