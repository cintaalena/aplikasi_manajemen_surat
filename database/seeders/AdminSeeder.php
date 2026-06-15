<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@kelurahan.local'],
            [
                'name'                  => 'SuperAdmin',
                'nip'                   => null,
                'email'                 => 'superadmin@kelurahan.local',
                'password'              => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'Admin123!')),
                'credential_code_hash'  => null,
                'jabatan'               => 'admin',
                'role'                  => 'admin',
                'is_active'             => true,
                'email_verified_at'     => now(),
            ]
        );
    }
}
