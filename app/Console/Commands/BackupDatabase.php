<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
                            {--keep=7 : Jumlah file backup yang dipertahankan}
                            {--dry-run : Tampilkan apa yang akan dilakukan tanpa benar-benar backup}';

    protected $description = 'Backup database ke storage/app/backups/ dan hapus backup lama';

    public function handle(): int
    {
        $keep   = (int) $this->option('keep');
        $dryRun = (bool) $this->option('dry-run');

        $host     = config('database.connections.mysql.host', '127.0.0.1');
        $port     = config('database.connections.mysql.port', '3306');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $database = config('database.connections.mysql.database');

        $backupDir  = storage_path('app/backups');
        $timestamp  = now()->format('Y-m-d_H-i-s');
        $backupFile = "{$backupDir}/backup_{$timestamp}.sql.gz";

        if ($dryRun) {
            $this->warn('[DRY RUN] Backup akan disimpan ke: ' . $backupFile);
            $this->warn('[DRY RUN] Backup lama akan disisakan: ' . $keep . ' file terbaru');
            return self::SUCCESS;
        }

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0750, true);
        }

        // Pastikan mysqldump tersedia
        exec('which mysqldump 2>/dev/null', $out, $which);
        if ($which !== 0) {
            $this->error('mysqldump tidak ditemukan. Pastikan mysql-client terinstall di server.');
            return self::FAILURE;
        }

        $dumpCmd = sprintf(
            'mysqldump -h %s -P %s -u %s --single-transaction --skip-lock-tables --routines %s | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($backupFile)
        );

        // Kirim password via env var agar tidak terlihat di daftar proses (ps aux)
        $descriptors = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $env         = array_merge($_ENV ?: [], ['MYSQL_PWD' => $password]);

        $proc = proc_open('bash -c ' . escapeshellarg($dumpCmd), $descriptors, $pipes, null, $env);

        if (! is_resource($proc)) {
            $this->error('Gagal memulai proses backup.');
            \Log::error('backup:database — gagal membuat proses mysqldump');
            return self::FAILURE;
        }

        fclose($pipes[0]);
        $stderr   = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        if ($exitCode !== 0 || ! file_exists($backupFile) || filesize($backupFile) === 0) {
            $this->error('Backup gagal.' . ($stderr ? ' Error: ' . trim($stderr) : ''));
            \Log::error('backup:database — mysqldump gagal', ['stderr' => $stderr]);
            // Hapus file kosong jika terbuat
            if (file_exists($backupFile)) {
                @unlink($backupFile);
            }
            return self::FAILURE;
        }

        $sizeMb = round(filesize($backupFile) / 1048576, 2);
        $this->info("Backup berhasil: {$backupFile} ({$sizeMb} MB)");
        \Log::info("backup:database — selesai: {$backupFile} ({$sizeMb} MB)");

        $this->rotateBackups($backupDir, $keep);

        return self::SUCCESS;
    }

    protected function rotateBackups(string $dir, int $keep): void
    {
        $files = glob("{$dir}/backup_*.sql.gz");
        if (! $files) {
            return;
        }

        rsort($files); // urutkan terbaru ke terlama
        $toDelete = array_slice($files, $keep);

        foreach ($toDelete as $file) {
            @unlink($file);
        }

        if (count($toDelete) > 0) {
            $this->info('Backup lama dihapus: ' . count($toDelete) . ' file.');
        }
    }
}
