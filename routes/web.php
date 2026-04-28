<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\LetterArchiveController;
use App\Http\Controllers\PendudukController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\LetterController;
use App\Http\Controllers\Api\LetterDocumentController;
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
    Route::post('/arsip-surat', [LetterArchiveController::class, 'store'])->name('arsip-surat.store');
    Route::get('/arsip-surat/{letter}', [LetterArchiveController::class, 'show'])->name('arsip-surat.show');
    Route::get('/arsip-surat/{letter}/pratinjau', [LetterArchiveController::class, 'pratinjau'])->name('arsip-surat.pratinjau');
    Route::get('/penduduk', [PendudukController::class, 'index'])->name('penduduk.index');
    Route::get('/penduduk/create', [PendudukController::class, 'create'])->name('penduduk.create');
    Route::post('/penduduk', [PendudukController::class, 'store'])->name('penduduk.store');
    Route::get('/penduduk/export', [PendudukController::class, 'export'])->name('penduduk.export');

    Route::get('/penduduk/search-by-name', [PendudukController::class, 'searchByName'])
        ->name('penduduk.searchByName');

    Route::get('/penduduk/search-kepala-keluarga', [PendudukController::class, 'searchKepalaKeluarga'])
        ->name('penduduk.searchKepalaKeluarga');

    Route::get('/penduduk/{penduduk}/edit', [PendudukController::class, 'edit'])->name('penduduk.edit');
    Route::put('/penduduk/{penduduk}', [PendudukController::class, 'update'])->name('penduduk.update');

    // SECURITY: File upload protected with secure.upload middleware
    Route::post('/penduduk/import', [PendudukController::class, 'import'])
        ->middleware('secure.upload')
        ->name('penduduk.import');

    // Finalize surat — pakai web route agar session auth bekerja
    Route::post('/surat/{templateSlug}/finalize', [LetterController::class, 'finalize'])
        ->middleware('throttle:10,1')
        ->name('surat.finalize');

    // Upload dokumen pendukung surat
    Route::post('/surat/dokumen/upload', [LetterDocumentController::class, 'upload'])
        ->middleware('throttle:30,1')
        ->name('surat.dokumen.upload');

    // Stream file dokumen langsung — bypass symlink & APP_URL
    Route::get('/surat/dokumen/{document}/file', [LetterDocumentController::class, 'file'])
        ->name('surat.dokumen.file');

    Route::delete('/surat/dokumen/{document}', [LetterDocumentController::class, 'destroy'])
        ->name('surat.dokumen.destroy');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

require __DIR__.'/auth.php';
