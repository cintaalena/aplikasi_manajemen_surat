<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soft deletes pada tabel penduduks:
     * Data penduduk yang dihapus tetap tersimpan di database (deleted_at terisi).
     * Ini penting untuk: audit trail, mencegah kehilangan data NIK/KTP warga,
     * dan kepatuhan terhadap regulasi perlindungan data pribadi.
     */
    public function up(): void
    {
        Schema::table('penduduks', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('penduduks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
