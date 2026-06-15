<?php

namespace App\Console\Commands;

use App\Models\LetterDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneOrphanDocuments extends Command
{
    protected $signature = 'dokumen:prune-orphan
                            {--hours=24 : Hapus dokumen tanpa surat yang lebih tua dari N jam}
                            {--dry-run : Tampilkan daftar dokumen yang akan dihapus tanpa benar-benar menghapus}';

    protected $description = 'Hapus dokumen yang sudah diunggah tapi tidak terikat ke surat manapun (letter_id = null)';

    public function handle(): int
    {
        $hours  = (int) $this->option('hours');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subHours($hours);

        $orphans = LetterDocument::whereNull('letter_id')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($orphans->isEmpty()) {
            $this->info("Tidak ada dokumen orphan yang lebih tua dari {$hours} jam.");
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$orphans->count()} dokumen orphan (lebih tua dari {$hours} jam).");

        if ($dryRun) {
            $this->warn('[DRY RUN] Dokumen yang akan dihapus:');
            foreach ($orphans as $doc) {
                $this->line("  - ID {$doc->id} | {$doc->original_name} | {$doc->created_at}");
            }
            return self::SUCCESS;
        }

        $deleted = 0;
        $failed  = 0;

        foreach ($orphans as $doc) {
            try {
                Storage::disk('public')->delete($doc->file_path);
                $doc->delete();
                $deleted++;
            } catch (\Throwable $e) {
                $failed++;
                \Log::warning("Gagal hapus dokumen orphan ID {$doc->id}: " . $e->getMessage());
            }
        }

        $this->info("Selesai. Berhasil dihapus: {$deleted}, Gagal: {$failed}.");

        return self::SUCCESS;
    }
}
