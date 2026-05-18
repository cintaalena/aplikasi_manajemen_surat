<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\LetterArchiveController;
use App\Http\Controllers\PendudukController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\LetterController;
use App\Http\Controllers\Api\LetterDocumentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\LetterNotificationController;
use App\Http\Controllers\DisposisiController;
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
        $lurahUser = \App\Models\User::where('jabatan', 'lurah')->first(['id', 'name', 'nip', 'jabatan']);
        return Inertia::render('SuratTemplates/Show', [
            'slug'      => $slug,
            'lurahUser' => $lurahUser,
        ]);
    })->name('surat-templates.show');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics'])->name('dashboard.metrics');
    Route::get('/dashboard/letters-by-month', [DashboardController::class, 'lettersByMonth'])->name('dashboard.letters-by-month');
    Route::get('/dashboard/rekap-surat', [DashboardController::class, 'rekapSurat'])->name('dashboard.rekap-surat');
    Route::get('/dashboard/export-excel', [DashboardController::class, 'exportExcel'])->name('dashboard.export.excel');
    
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Arsip Surat ────────────────────────────────────────────────────────
    Route::get('/arsip-surat', [LetterArchiveController::class, 'index'])->name('arsip-surat.index');
    Route::get('/arsip-surat/{letter}', [LetterArchiveController::class, 'show'])->name('arsip-surat.show');
    Route::get('/arsip-surat/{letter}/pratinjau', [LetterArchiveController::class, 'pratinjau'])->name('arsip-surat.pratinjau');
    Route::post('/arsip-surat/{letter}/viewed', [LetterArchiveController::class, 'markViewed'])->name('arsip-surat.viewed');

    // Tambah arsip manual — staff & admin saja
    Route::post('/arsip-surat', [LetterArchiveController::class, 'store'])
        ->middleware('role:staff,admin')
        ->name('arsip-surat.store');

    // ── Penduduk — lihat boleh semua, create/edit hanya staff & admin ──────
    Route::get('/penduduk', [PendudukController::class, 'index'])->name('penduduk.index');
    Route::get('/penduduk/export', [PendudukController::class, 'export'])->name('penduduk.export');
    Route::get('/penduduk/search-by-name', [PendudukController::class, 'searchByName'])->name('penduduk.searchByName');
    Route::get('/penduduk/search-kepala-keluarga', [PendudukController::class, 'searchKepalaKeluarga'])->name('penduduk.searchKepalaKeluarga');
    Route::get('/penduduk/cari-istri', [PendudukController::class, 'cariIstri'])->name('penduduk.cariIstri');

    Route::middleware('role:staff,admin')->group(function () {
        Route::get('/penduduk/create', [PendudukController::class, 'create'])->name('penduduk.create');
        Route::post('/penduduk', [PendudukController::class, 'store'])->name('penduduk.store');
        Route::get('/penduduk/{penduduk}/edit', [PendudukController::class, 'edit'])->name('penduduk.edit');
        Route::put('/penduduk/{penduduk}', [PendudukController::class, 'update'])->name('penduduk.update');
        Route::delete('/penduduk/{penduduk}', [PendudukController::class, 'destroy'])->name('penduduk.destroy');
    });

    // Import — admin saja
    Route::post('/penduduk/import', [PendudukController::class, 'import'])
        ->middleware(['secure.upload', 'role:admin'])
        ->name('penduduk.import');

    // ── Template & Finalize Surat — staff & admin saja ─────────────────────
    Route::middleware('role:staff,admin')->group(function () {
        Route::post('/surat/{templateSlug}/finalize', [LetterController::class, 'finalize'])
            ->middleware('throttle:10,1')
            ->name('surat.finalize');
    });

    // Upload & stream dokumen surat — semua role yang login
    Route::post('/surat/dokumen/upload', [LetterDocumentController::class, 'upload'])
        ->middleware('throttle:30,1')
        ->name('surat.dokumen.upload');

    Route::get('/surat/dokumen/{document}/file', [LetterDocumentController::class, 'file'])
        ->name('surat.dokumen.file');

    Route::delete('/surat/dokumen/{document}', [LetterDocumentController::class, 'destroy'])
        ->name('surat.dokumen.destroy');

    // ── Notifikasi — lurah & staff ────────────────────────────────────────────
    Route::middleware('role:lurah,staff')->group(function () {
        Route::get('/notifications', [LetterNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/stream', [LetterNotificationController::class, 'stream'])->name('notifications.stream');
        Route::post('/notifications/mark-all-read', [LetterNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::patch('/notifications/{notification}/mark-read', [LetterNotificationController::class, 'markRead'])->name('notifications.mark-read');
    });

    // ── Disposisi Surat — lurah mendisposisi, staff melihat tugas ─────────
    Route::get('/disposisi/staff-list', [DisposisiController::class, 'staffList'])
        ->middleware('role:lurah')
        ->name('disposisi.staff-list');
    Route::post('/arsip-surat/{letter}/disposisi', [DisposisiController::class, 'store'])
        ->middleware('role:lurah')
        ->name('disposisi.store');
    Route::get('/disposisi-tugas', [DisposisiController::class, 'index'])
        ->middleware('role:staff')
        ->name('disposisi-tugas.index');
    Route::patch('/disposisi-tugas/{disposisi}/diterima', [DisposisiController::class, 'markDiterima'])
        ->middleware('role:staff')
        ->name('disposisi-tugas.diterima');
    Route::patch('/disposisi-tugas/{disposisi}/selesai', [DisposisiController::class, 'markSelesai'])
        ->middleware('role:staff')
        ->name('disposisi-tugas.selesai');

    // ── Admin — manajemen pengguna ──────────────────────────────────────────
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/pengguna', [AdminUserController::class, 'index'])->name('admin.pengguna.index');
        Route::post('/pengguna', [AdminUserController::class, 'store'])->name('admin.pengguna.store');
        Route::put('/pengguna/{user}', [AdminUserController::class, 'update'])->name('admin.pengguna.update');
        Route::patch('/pengguna/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('admin.pengguna.toggle-active');
        Route::patch('/pengguna/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('admin.pengguna.reset-password');
    });
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

require __DIR__.'/auth.php';
