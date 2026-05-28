<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE letter_dispositions MODIFY COLUMN status ENUM('pending', 'diterima', 'selesai') DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Kembalikan ke hanya pending/selesai (record diterima menjadi pending)
        DB::statement("UPDATE letter_dispositions SET status = 'pending' WHERE status = 'diterima'");
        DB::statement("ALTER TABLE letter_dispositions MODIFY COLUMN status ENUM('pending', 'selesai') DEFAULT 'pending'");
    }
};
