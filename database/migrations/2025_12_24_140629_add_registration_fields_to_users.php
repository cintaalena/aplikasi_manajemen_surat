<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        if (!Schema::hasColumn('users', 'jabatan')) {
            $table->string('jabatan', 50);
        }
        if (!Schema::hasColumn('users', 'credential_code_hash')) {
            $table->string('credential_code_hash')->nullable();
        }
        if (!Schema::hasColumn('users', 'credential_issued_at')) {
            $table->timestamp('credential_issued_at')->nullable();
        }
        if (!Schema::hasColumn('users', 'is_active')) {
            $table->boolean('is_active')->default(false);
        }
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        if (Schema::hasColumn('users', 'credential_code_hash')) {
            $table->dropColumn('credential_code_hash');
        }
        if (Schema::hasColumn('users', 'credential_issued_at')) {
            $table->dropColumn('credential_issued_at');
        }
        if (Schema::hasColumn('users', 'is_active')) {
            $table->dropColumn('is_active');
        }
        // jabatan jangan didrop kalau kolom ini sudah ada dari sebelumnya/legacy
        // kalau Anda yakin jabatan hanya dibuat dari migration ini, baru drop di sini.
    });
}
};
