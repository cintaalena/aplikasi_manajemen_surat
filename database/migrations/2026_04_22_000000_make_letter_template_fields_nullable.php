<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Pastikan kolom-kolom yang dibutuhkan surat masuk manual bersifat nullable.
        // Beberapa mungkin sudah nullable dari migrasi sebelumnya — cek dulu.
        $columns = collect(DB::select('SHOW COLUMNS FROM letters'))->keyBy('Field');

        if (($columns['template_slug']->Null ?? 'NO') === 'NO') {
            DB::statement('ALTER TABLE letters MODIFY template_slug VARCHAR(255) NULL');
        }
        if (($columns['payload']->Null ?? 'NO') === 'NO') {
            DB::statement('ALTER TABLE letters MODIFY payload JSON NULL');
        }
        if (($columns['printed_at']->Null ?? 'NO') === 'NO') {
            DB::statement('ALTER TABLE letters MODIFY printed_at TIMESTAMP NULL');
        }
        // index_code sudah varchar di database ini, cukup pastikan nullable
        if (($columns['index_code']->Null ?? 'NO') === 'NO') {
            $type = $columns['index_code']->Type ?? 'varchar(50)';
            DB::statement("ALTER TABLE letters MODIFY index_code {$type} NULL");
        }
        if (($columns['urut']->Null ?? 'NO') === 'NO') {
            DB::statement('ALTER TABLE letters MODIFY urut BIGINT UNSIGNED NULL');
        }
        if (($columns['month_roman']->Null ?? 'NO') === 'NO') {
            DB::statement('ALTER TABLE letters MODIFY month_roman VARCHAR(4) NULL');
        }
        if (($columns['year']->Null ?? 'NO') === 'NO') {
            DB::statement('ALTER TABLE letters MODIFY year SMALLINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Tidak di-revert karena bisa merusak data existing
    }
};
