<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Revert nik column from TEXT/BLOB back to VARCHAR(20) if it was changed
        $columns = DB::select("SHOW COLUMNS FROM penduduks LIKE 'nik'");
        if (!empty($columns)) {
            $type = strtolower($columns[0]->Type ?? '');
            if (str_contains($type, 'text') || str_contains($type, 'blob')) {
                DB::statement('ALTER TABLE penduduks MODIFY COLUMN `nik` VARCHAR(20) NULL');
            }
        }

        // Re-add unique index if missing (may have been dropped by failed migration)
        $indexes = DB::select("SHOW INDEX FROM penduduks WHERE Key_name = 'penduduks_nik_unique'");
        if (empty($indexes)) {
            // Remove any duplicates before adding unique constraint (keep lower id)
            DB::statement('DELETE p1 FROM penduduks p1
                INNER JOIN penduduks p2
                WHERE p1.id > p2.id AND p1.nik = p2.nik AND p1.nik IS NOT NULL');

            DB::statement("ALTER TABLE penduduks ADD UNIQUE KEY `penduduks_nik_unique` (`nik`)");
        }
    }

    public function down(): void
    {
        // Remove the index we added (if any)
        $indexes = DB::select("SHOW INDEX FROM penduduks WHERE Key_name = 'penduduks_nik_unique'");
        if (!empty($indexes)) {
            DB::statement('ALTER TABLE penduduks DROP INDEX `penduduks_nik_unique`');
        }
    }
};
