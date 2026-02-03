<?php

namespace Database\Seeders;

// database/seeders/KelurahanCredentialSeeder.php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KelurahanCredentialSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('kelurahan_credentials')->insert([
            ['code' => 'A-001', 'jabatan' => 'Lurah', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'B-001', 'jabatan' => 'Operator', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

