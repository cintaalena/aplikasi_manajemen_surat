<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE penduduks MODIFY COLUMN status_kehidupan ENUM('Hidup', 'Meninggal', 'Pindah') NOT NULL DEFAULT 'Hidup'");
    }

    public function down(): void
    {
        // Ubah kembali record Pindah -> Hidup sebelum remove enum value
        DB::statement("UPDATE penduduks SET status_kehidupan = 'Hidup' WHERE status_kehidupan = 'Pindah'");
        DB::statement("ALTER TABLE penduduks MODIFY COLUMN status_kehidupan ENUM('Hidup', 'Meninggal') NOT NULL DEFAULT 'Hidup'");
    }
};
