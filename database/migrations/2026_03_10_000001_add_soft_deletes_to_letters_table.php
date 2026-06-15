<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soft deletes pada tabel letters:
     * Surat yang "dihapus" tidak benar-benar hilang dari database,
     * sehingga dapat dipulihkan dan tetap tersimpan untuk keperluan audit.
     */
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
