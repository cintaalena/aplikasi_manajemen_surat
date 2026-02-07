<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LetterCounterController;
use App\Http\Controllers\Api\LetterIndexController;
use App\Http\Controllers\Api\LetterController;
use App\Http\Controllers\Api\RateLimitTestController;

Route::get('/letter-counters/{templateSlug}', [LetterCounterController::class, 'show']);
Route::post('/letter-counters/{templateSlug}/increment', [LetterCounterController::class, 'increment']);
Route::get('/letter-index-groups', [LetterIndexController::class, 'groups']);

// Rate limiting: Max 10 finalize per menit per user (mencegah spam penomoran surat)
Route::post('/letters/{templateSlug}/finalize', [LetterController::class, 'finalize'])
    ->middleware(['auth', 'throttle:10,1']);

// TEST ONLY - Rate Limiting Test Endpoints (HAPUS di production!)
Route::prefix('test/rate-limit')->group(function () {
    Route::post('/otp-request', [RateLimitTestController::class, 'testOtpRequest'])
        ->middleware('throttle:3,10');
    
    Route::post('/otp-verify', [RateLimitTestController::class, 'testOtpVerify'])
        ->middleware('throttle:5,5');
    
    Route::post('/login', [RateLimitTestController::class, 'testLogin'])
        ->middleware('throttle:5,1');
});