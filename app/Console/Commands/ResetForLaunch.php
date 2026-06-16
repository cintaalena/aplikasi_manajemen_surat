<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetForLaunch extends Command
{
    protected $signature = 'db:reset-launch
                            {--with-users : Hapus semua pengguna kecuali admin, lalu jalankan AdminSeeder}
                            {--force : Lewati konfirmasi (untuk environment non-production)}';

    protected $description = 'Reset data uji coba sebelum diserahkan ke pengguna. Nomor surat kembali dari 1.';

    // Tabel yang berisi data operasional — dikosongkan semua
    private const DATA_TABLES = [
        'letter_views',
        'letter_notifications',
        'letter_dispositions',
        'letter_documents',
        'letters',
        'letter_counters',
        'penduduks',
        'activity_logs',
        'security_events',
    ];

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $confirmed = $this->confirm(
                '⚠️  Ini akan menghapus SEMUA data surat dan penduduk di ' . config('app.url') . '. Lanjutkan?',
                false
            );

            if (! $confirmed) {
                $this->info('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        $this->info('Memulai reset data pre-launch...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach (self::DATA_TABLES as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  ✓ {$table} dikosongkan");
            }
        }

        if ($this->option('with-users')) {
            $deleted = DB::table('users')->where('role', '!=', 'admin')->delete();
            $this->line("  ✓ {$deleted} pengguna non-admin dihapus");

            $this->call('db:seed', ['--class' => 'AdminSeeder', '--force' => true]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->newLine();
        $this->info('✅ Reset selesai. Nomor surat berikutnya akan dimulai dari 001.');

        if (! $this->option('with-users')) {
            $this->comment('   Tip: Jalankan dengan --with-users jika ingin hapus akun staf uji coba juga.');
        }

        return self::SUCCESS;
    }
}
