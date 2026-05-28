<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Api\LetterCounterController;
use App\Http\Controllers\Api\LetterIndexController;
use App\Http\Controllers\Api\LetterController;
use App\Http\Controllers\PendudukController;

// Public API - data read-only yang tidak sensitif
// SECURITY (A04): Throttle public endpoints to prevent enumeration/DoS
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/letter-index-groups', [LetterIndexController::class, 'groups']);
    Route::get('/letter-counters/{templateSlug}', [LetterCounterController::class, 'show']);
});

// SECURITY (A10 - SSRF): Wilayah proxy — URLs are restricted to a hardcoded
// allow-listed domain (ibnux.github.io). IDs are stripped of non-digits before
// being interpolated into the URL, preventing path traversal. Throttled to
// prevent the proxy from being used as a DoS amplifier.
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/wilayah/provinces', function () {
        return Cache::remember('wilayah_provinces', 86400, function () {
            $res = Http::timeout(10)->get('https://ibnux.github.io/data-indonesia/provinsi.json');
            return $res->successful() ? $res->json() : [];
        });
    });

    Route::get('/wilayah/regencies/{provinsiId}', function (string $provinsiId) {
        $provinsiId = preg_replace('/\D/', '', $provinsiId);
        if (!$provinsiId) return response()->json([], 400);
        return Cache::remember("wilayah_regencies_{$provinsiId}", 86400, function () use ($provinsiId) {
            $res = Http::timeout(10)->get("https://ibnux.github.io/data-indonesia/kabupaten/{$provinsiId}.json");
            return $res->successful() ? $res->json() : [];
        });
    });

    Route::get('/wilayah/districts/{kabupatenId}', function (string $kabupatenId) {
        $kabupatenId = preg_replace('/\D/', '', $kabupatenId);
        if (!$kabupatenId) return response()->json([], 400);
        return Cache::remember("wilayah_districts_{$kabupatenId}", 86400, function () use ($kabupatenId) {
            $res = Http::timeout(10)->get("https://ibnux.github.io/data-indonesia/kecamatan/{$kabupatenId}.json");
            return $res->successful() ? $res->json() : [];
        });
    });

    Route::get('/wilayah/villages/{kecamatanId}', function (string $kecamatanId) {
        $kecamatanId = preg_replace('/\D/', '', $kecamatanId);
        if (!$kecamatanId) return response()->json([], 400);
        return Cache::remember("wilayah_villages_{$kecamatanId}", 86400, function () use ($kecamatanId) {
            $res = Http::timeout(10)->get("https://ibnux.github.io/data-indonesia/kelurahan/{$kecamatanId}.json");
            return $res->successful() ? $res->json() : [];
        });
    });
});

// Semua API route yang mengubah data / data sensitif memerlukan autentikasi
Route::middleware(['auth'])->group(function () {
    // SECURITY (A01): Counter increment restricted to staff & admin only.
    // Any authenticated user (e.g. lurah viewing only) must not be able to
    // increment letter counters and corrupt the numbering sequence.
    Route::post('/letter-counters/{templateSlug}/increment', [LetterCounterController::class, 'increment'])
        ->middleware('role:staff,admin');
});