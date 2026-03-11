<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom recovery_email ke tabel users.
     *
     * Email pemulihan dipakai KHUSUS untuk menerima link reset password.
     * Wajib berbeda dari email akun utama (divalidasi di layer aplikasi).
     * Nullable agar akun yang sudah ada sebelum kolom ini ditambahkan tetap valid.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('recovery_email')->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['recovery_email']);
            $table->dropColumn('recovery_email');
        });
    }
};
