<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'admin@kelurahan.test')->exists()) {
            return;
        }

        $credentialPlain = 'A-001';

        User::create([
            'name' => 'Admin Kelurahan',
            'email' => 'admin@kelurahan.test',
            'phone' => '0000000000',
            'jabatan' => 'Lurah',
            'password' => Hash::make('password123'),
            'credential_code_hash' => Hash::make($credentialPlain),
            'credential_issued_at' => now(),
        ]);
    }
}
