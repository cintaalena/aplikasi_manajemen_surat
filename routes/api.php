<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Api\LetterCounterController;
use App\Http\Controllers\Api\LetterIndexController;
use App\Http\Controllers\Api\LetterController;
use App\Http\Controllers\PendudukController;

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/letter-index-groups', [LetterIndexController::class, 'groups']);
    Route::get('/letter-counters/{templateSlug}', [LetterCounterController::class, 'show']);
});

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

Route::middleware(['auth'])->group(function () {
    Route::post('/letter-counters/{templateSlug}/increment', [LetterCounterController::class, 'increment'])
        ->middleware('role:staff,admin');
});