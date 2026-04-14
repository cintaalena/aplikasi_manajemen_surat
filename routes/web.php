<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\LetterArchiveController;
use App\Http\Controllers\PendudukController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\LetterController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
    ]);
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
    Route::get('/dashboard/letters-by-month', [DashboardController::class, 'lettersByMonth'])->name('dashboard.letters-by-month');
    Route::get('/dashboard/export-excel', [DashboardController::class, 'exportExcel'])->name('dashboard.export.excel');
    
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/arsip-surat', [LetterArchiveController::class, 'index'])->name('arsip-surat.index');
    Route::get('/penduduk', [PendudukController::class, 'index'])->name('penduduk.index');
    Route::get('/penduduk/create', [PendudukController::class, 'create'])->name('penduduk.create');
    Route::post('/penduduk', [PendudukController::class, 'store'])->name('penduduk.store');
    Route::get('/penduduk/export', [PendudukController::class, 'export'])->name('penduduk.export');

    Route::get('/penduduk/search-by-name', [PendudukController::class, 'searchByName'])
        ->name('penduduk.searchByName');

    Route::get('/penduduk/search-kepala-keluarga', [PendudukController::class, 'searchKepalaKeluarga'])
        ->name('penduduk.searchKepalaKeluarga');
        
    // SECURITY: File upload protected with secure.upload middleware
    Route::post('/penduduk/import', [PendudukController::class, 'import'])
        ->middleware('secure.upload')
        ->name('penduduk.import');

    // Finalize surat — pakai web route agar session auth bekerja
    Route::post('/surat/{templateSlug}/finalize', [LetterController::class, 'finalize'])
        ->middleware('throttle:10,1')
        ->name('surat.finalize');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

require __DIR__.'/auth.php';
