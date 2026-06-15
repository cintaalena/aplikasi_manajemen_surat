<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SECURITY (A02 — Cryptographic Failures):
 * Enkripsi kolom PII (Personally Identifiable Information) pada tabel penduduks
 * menggunakan AES-256-CBC via Laravel Crypt (APP_KEY sebagai kunci enkripsi).
 *
 * Kolom yang dienkripsi:
 *   - nik               : Nomor Induk Kependudukan
 *   - nama              : Nama lengkap penduduk
 *   - nama_kepala_keluarga : Nama kepala keluarga
 *   - alamat            : Alamat tempat tinggal
 *   - tempat_lahir      : Tempat lahir
 *
 * Perubahan struktur:
 *   - Kolom di atas diubah ke TEXT untuk menampung ciphertext base64 AES-256-CBC
 *   - UNIQUE index pada `nik` dihapus (AES-CBC non-deterministik: plaintext yang
 *     sama menghasilkan ciphertext berbeda karena IV acak)
 *   - Kolom `nik_hash` (HMAC-SHA256) ditambahkan sebagai pengganti UNIQUE NIK
 *     untuk deteksi duplikasi yang aman
 *   - Data yang sudah ada dienkripsi secara langsung di migrasi ini
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penduduks', function (Blueprint $table) {
            $table->text('nik')->nullable()->change();
            $table->text('nama')->change();
            $table->text('nama_kepala_keluarga')->nullable()->change();
            $table->text('alamat')->nullable()->change();
            $table->text('tempat_lahir')->nullable()->change();
        });

        Schema::table('penduduks', function (Blueprint $table) {
            $table->dropUnique(['nik']);

            $table->dropIndex(['nama']);

            $table->string('nik_hash', 64)
                  ->nullable()
                  ->after('nik')
                  ->comment('HMAC-SHA256 of plaintext NIK — used for secure deduplication');

            $table->index('nik_hash');
        });

        DB::table('penduduks')->orderBy('id')->each(function ($row) {
            $updates = [];

            if ($row->nik !== null) {
                $updates['nik']      = Crypt::encryptString($row->nik);
                $updates['nik_hash'] = hash_hmac('sha256', $row->nik, config('app.key'));
            }

            if ($row->nama !== null) {
                $updates['nama'] = Crypt::encryptString($row->nama);
            }

            if ($row->nama_kepala_keluarga !== null) {
                $updates['nama_kepala_keluarga'] = Crypt::encryptString($row->nama_kepala_keluarga);
            }

            if ($row->alamat !== null) {
                $updates['alamat'] = Crypt::encryptString($row->alamat);
            }

            if ($row->tempat_lahir !== null) {
                $updates['tempat_lahir'] = Crypt::encryptString($row->tempat_lahir);
            }

            if (! empty($updates)) {
                DB::table('penduduks')->where('id', $row->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        DB::table('penduduks')->orderBy('id')->each(function ($row) {
            $updates = [];

            try {
                if ($row->nik !== null) {
                    $updates['nik'] = Crypt::decryptString($row->nik);
                }
            } catch (\Exception $e) {}

            try {
                if ($row->nama !== null) {
                    $updates['nama'] = Crypt::decryptString($row->nama);
                }
            } catch (\Exception $e) {}

            try {
                if ($row->nama_kepala_keluarga !== null) {
                    $updates['nama_kepala_keluarga'] = Crypt::decryptString($row->nama_kepala_keluarga);
                }
            } catch (\Exception $e) {}

            try {
                if ($row->alamat !== null) {
                    $updates['alamat'] = Crypt::decryptString($row->alamat);
                }
            } catch (\Exception $e) {}

            try {
                if ($row->tempat_lahir !== null) {
                    $updates['tempat_lahir'] = Crypt::decryptString($row->tempat_lahir);
                }
            } catch (\Exception $e) {}

            if (! empty($updates)) {
                DB::table('penduduks')->where('id', $row->id)->update($updates);
            }
        });

        Schema::table('penduduks', function (Blueprint $table) {
            $table->dropIndex(['nik_hash']);
            $table->dropColumn('nik_hash');
        });

        Schema::table('penduduks', function (Blueprint $table) {
            $table->string('nik', 20)->nullable()->change();
            $table->string('nama', 150)->change();
            $table->string('nama_kepala_keluarga', 150)->nullable()->change();
            $table->string('alamat', 255)->nullable()->change();
            $table->string('tempat_lahir', 100)->nullable()->change();

            $table->unique('nik');
            $table->index('nama');
        });
    }
};
