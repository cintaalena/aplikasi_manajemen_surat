<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SoundPingNotification extends Command
{
    protected $signature = 'notif:sound-ping
                            {--user= : ID pengguna lurah tertentu (opsional, default semua lurah aktif)}';

    protected $description = 'Picu bunyi notifikasi di browser lurah tanpa membuat rekaman di kotak notifikasi';

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=cyan>====================================</>');
        $this->line('<fg=cyan>  TEST BUNYI NOTIFIKASI (sound only)</>');
        $this->line('<fg=cyan>====================================</>');
        $this->newLine();

        $query = User::where('role', 'lurah')->where('is_active', true);

        if ($userId = $this->option('user')) {
            if (! ctype_digit((string) $userId)) {
                $this->error('Opsi --user harus berupa angka ID.');
                return self::FAILURE;
            }
            $query->where('id', (int) $userId);
        }

        $lurahan = $query->get(['id', 'name']);

        if ($lurahan->isEmpty()) {
            $this->error('Tidak ada akun lurah aktif ditemukan.');
            return self::FAILURE;
        }

        foreach ($lurahan as $lurah) {
            // Tulis ping ke cache — SSE stream akan membaca & langsung hapus
            Cache::put("notif_sound_ping_{$lurah->id}", true, now()->addMinutes(5));
            $this->line("<fg=green>✓</> Sound ping dikirim ke: <fg=yellow>{$lurah->name}</> (ID: {$lurah->id})");
        }

        $this->newLine();
        $this->line('<fg=cyan>Selanjutnya:</> Pastikan browser Pak Lurah terbuka & sudah pernah diklik.');
        $this->line('Bunyi akan terdengar dalam <fg=yellow>maks 3 detik</>. Tidak ada pesan di kotak notifikasi.');
        $this->newLine();

        return self::SUCCESS;
    }
}
