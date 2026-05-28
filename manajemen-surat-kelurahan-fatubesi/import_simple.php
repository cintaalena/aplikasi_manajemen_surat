<?php
/**
 * Simple Import Script for database_penduduk.xlsx
 * Usage: php import_simple.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "   IMPORT DATABASE PENDUDUK - FATUBESI\n";
echo "========================================\n\n";

// Check for Excel file
$excelFile = __DIR__ . '/database_penduduk.xlsx';
if (!file_exists($excelFile)) {
    $excelFile = __DIR__ . '/database_penduduk.xls';
    if (!file_exists($excelFile)) {
        echo "ERROR: File database_penduduk.xlsx atau .xls tidak ditemukan!\n";
        exit(1);
    }
}

echo "[1/3] File ditemukan: " . basename($excelFile) . "\n";

// Test database connection
echo "[2/3] Cek koneksi database...\n";
try {
    DB::connection()->getPdo();
    echo "✓ Database terhubung\n\n";
} catch (Exception $e) {
    echo "ERROR: Database tidak terhubung!\n";
    echo "Pastikan MySQL/XAMPP sudah running!\n";
    echo "Detail: " . $e->getMessage() . "\n";
    exit(1);
}

// Import via controller
echo "[3/3] Memproses import...\n\n";

try {
    $file = new \Illuminate\Http\UploadedFile(
        $excelFile,
        basename($excelFile),
        mime_content_type($excelFile),
        null,
        true // test mode
    );
    
    $controller = new \App\Http\Controllers\PendudukController();
    $request = new \Illuminate\Http\Request();
    $request->files->set('file', $file);
    
    // Call import method
    $result = $controller->import($request);
    
    // Check for success/error messages in session
    if (session()->has('success')) {
        echo "\n✓ IMPORT BERHASIL!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo session()->get('success') . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    } elseif (session()->has('error')) {
        echo "\n✗ IMPORT GAGAL!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo session()->get('error') . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    } else {
        echo "\n✓ Import selesai (lihat hasil di web)\n";
    }
    
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}

// Verify hasil
echo "\n[Verifikasi] Cek jumlah data...\n";
$totalData = DB::table('penduduks')->count();

if ($totalData > 0) {
    echo "✓ Total data di database: $totalData baris\n\n";
    
    // Sample data
    echo "Sample 3 data pertama:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $samples = DB::table('penduduks')->limit(3)->get();
    foreach ($samples as $p) {
        echo "• NIK: {$p->nik} - {$p->nama} ({$p->dusun}/{$p->rt}/{$p->rw})\n";
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
} else {
    echo "WARNING: Tidak ada data di database!\n";
    echo "Coba periksa format Excel atau lihat error di atas.\n";
}

echo "\n========================================\n";
echo "          IMPORT SELESAI!              \n";
echo "========================================\n\n";
echo "Akses di browser: http://127.0.0.1:8000/penduduk\n\n";
