<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LetterCounterController;
use App\Http\Controllers\Api\LetterIndexController;
use App\Http\Controllers\Api\LetterController;

// Semua API route memerlukan autentikasi
Route::middleware(['auth'])->group(function () {
    // Kode indeks surat (read-only, tidak mengubah data)
    Route::get('/letter-index-groups', [LetterIndexController::class, 'groups']);

    // Counter surat — hanya pengguna terautentikasi yang boleh membaca/increment
    Route::get('/letter-counters/{templateSlug}', [LetterCounterController::class, 'show']);
    Route::post('/letter-counters/{templateSlug}/increment', [LetterCounterController::class, 'increment']);

    // Finalize surat — rate limit 10x/menit per user untuk mencegah spam penomoran
    Route::post('/letters/{templateSlug}/finalize', [LetterController::class, 'finalize'])
        ->middleware('throttle:10,1');
});