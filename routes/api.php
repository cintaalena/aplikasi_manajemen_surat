<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LetterCounterController;
use App\Http\Controllers\Api\LetterIndexController;
use App\Http\Controllers\Api\LetterController;
use App\Http\Controllers\PendudukController;

// Public API - data read-only yang tidak sensitif
Route::get('/letter-index-groups', [LetterIndexController::class, 'groups']);
Route::get('/letter-counters/{templateSlug}', [LetterCounterController::class, 'show']);

// Semua API route yang mengubah data / data sensitif memerlukan autentikasi
Route::middleware(['auth'])->group(function () {
    Route::post('/letter-counters/{templateSlug}/increment', [LetterCounterController::class, 'increment']);

});