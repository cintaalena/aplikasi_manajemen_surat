<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LetterCounterController;
use App\Http\Controllers\Api\LetterIndexController;
use App\Http\Controllers\Api\LetterController;

Route::get('/letter-counters/{templateSlug}', [LetterCounterController::class, 'show']);
Route::post('/letter-counters/{templateSlug}/increment', [LetterCounterController::class, 'increment']);
Route::get('/letter-index-groups', [LetterIndexController::class, 'groups']);
Route::post('/letters/{templateSlug}/finalize', [LetterController::class, 'finalize']);