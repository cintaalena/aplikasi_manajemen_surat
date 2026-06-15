<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup database setiap hari pukul 01.00 WITA, simpan 7 backup terakhir
Schedule::command('backup:database --keep=7')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->runInBackground();

// Hapus dokumen yang diunggah tapi surat dibatalkan, setiap hari pukul 02.00 WITA
Schedule::command('dokumen:prune-orphan --hours=24')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();
