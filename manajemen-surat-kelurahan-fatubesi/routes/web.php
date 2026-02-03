<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisterOtpController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\LetterArchiveController;
use App\Http\Controllers\PendudukController;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});


Route::middleware('guest')->group(function () {
    Route::get('/register', function () {
        return Inertia::render('Auth/Register');
    })->name('register');
    Route::get('/register/success', function () {
    return Inertia::render('Auth/RegisterSuccess');
    })->name('register.success');   
    
    Route::post('/register/request-otp', [RegisterOtpController::class, 'requestOtp'])
        ->name('register.request-otp');

    Route::post('/register/verify-otp', [RegisterOtpController::class, 'verifyOtp'])
        ->name('register.verify-otp');
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/template-surat', function () {
        return Inertia::render('SuratTemplates/Index');
    })->name('surat-templates.index');

    Route::get('/template-surat/{slug}', function (string $slug) {
        return Inertia::render('SuratTemplates/Show', [
            'slug' => $slug,
        ]);
    })->name('surat-templates.show');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics'])->name('dashboard.metrics');
    
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/arsip-surat', [LetterArchiveController::class, 'index'])->name('arsip-surat.index');
    Route::get('/penduduk', [PendudukController::class, 'index'])->name('penduduk.index');
    Route::get('/penduduk/export', [PendudukController::class, 'export'])->name('penduduk.export');
    Route::post('/penduduk/import', [PendudukController::class, 'import'])->name('penduduk.import');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

require __DIR__.'/auth.php';
