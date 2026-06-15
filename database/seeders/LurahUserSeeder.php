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
            'name'            => 'ANAK AGUNG G.S.M. PUTRA SE',
            'nip'             => '19760703 200112 1 002',
            'email'           => 'anakagung.lurah@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'A-001',
            'jabatan'         => 'lurah',
            'role'            => 'lurah',
        ],

        [
            'name'            => 'YUBLINA BAUBANI, A.Md',
            'nip'             => '19750601 200012 2 007',
            'email'           => 'yublina.sekretaris@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'B-001',
            'jabatan'         => 'sekretaris',
            'role'            => 'staff',
        ],

        [
            'name'            => 'FERRY FRANSISKA, S.Sos',
            'nip'             => '19730618201001 1004',
            'email'           => 'ferry.kasie.pelayanan@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'B-001',
            'jabatan'         => 'kasie_pelayanan_masyarakat',
            'role'            => 'staff',
        ],

        [
            'name'            => 'YERRY AGUSTINUS BALLU, SH',
            'nip'             => '19840803 201001 1 006',
            'email'           => 'yerry.kasie.trantib@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'B-001',
            'jabatan'         => 'kasie_pem_trantib_umum',
            'role'            => 'staff',
        ],

        [
            'name'            => 'SELESTINA ANUNUT, A.Md',
            'nip'             => '19810920 200502 2 008',
            'email'           => 'selestina.pengelola@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'B-001',
            'jabatan'         => 'pengelola_pemberdayaan_masyarakat',
            'role'            => 'staff',
        ],

        [
            'name'            => 'THERESIA MAMO',
            'nip'             => '19730515 199803 2 009',
            'email'           => 'theresia.pengadmin1@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'B-001',
            'jabatan'         => 'pengadministrasi_perkantoran',
            'role'            => 'staff',
        ],
        [
            'name'            => 'MARSELINA LAOHINE',
            'nip'             => '19850521 201406 2 006',
            'email'           => 'marselina.pengadmin2@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'B-001',
            'jabatan'         => 'pengadministrasi_perkantoran',
            'role'            => 'staff',
        ],
        [
            'name'            => 'YERMIAS HANING',
            'nip'             => '19820704 202525 1 023',
            'email'           => 'yermias.pengadmin3@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'B-001',
            'jabatan'         => 'pengadministrasi_perkantoran',
            'role'            => 'staff',
        ],
        [
            'name'            => 'CORNALIA ALIKE SALAWANI',
            'nip'             => '19850810 202521 2 027',
            'email'           => 'cornalia.pengadmin4@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'B-001',
            'jabatan'         => 'pengadministrasi_perkantoran',
            'role'            => 'staff',
        ],
        [
            'name'            => 'SOFIA MEIDIANA PAH',
            'nip'             => '19850510 202521 2 038',
            'email'           => 'sofia.pengadmin5@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'B-001',
            'jabatan'         => 'pengadministrasi_perkantoran',
            'role'            => 'staff',
        ],

        [
            'name'            => 'YOHANES PAULUS NESI LEBAO, ST',
            'nip'             => '19900523 202521 1 063',
            'email'           => 'yohanes.penata1@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'B-001',
            'jabatan'         => 'penata_layanan_operasional',
            'role'            => 'staff',
        ],
        [
            'name'            => 'CLARA PRISCILLA DARIS, S.AB',
            'nip'             => '7121199704260128',
            'email'           => 'clara.penata2@kelurahan.local',
            'password'        => 'Kelurahan2024!',
            'credential_code' => 'B-001',
            'jabatan'         => 'penata_layanan_operasional',
            'role'            => 'staff',
        ],
    ];

    public function run(): void
    {
        foreach ($this->users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name'                  => $userData['name'],
                    'nip'                   => $userData['nip'] ?? null,
                    'email'                 => $userData['email'],
                    'password'              => Hash::make($userData['password']),
                    'credential_code_hash'  => Hash::make(strtoupper($userData['credential_code'])),
                    'jabatan'               => $userData['jabatan'],
                    'role'                  => $userData['role'],
                    'is_active'             => true,
                    'email_verified_at'     => now(),
                ]
            );
        }
    }
}