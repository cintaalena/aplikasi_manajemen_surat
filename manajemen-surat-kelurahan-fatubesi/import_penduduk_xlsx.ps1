# Script Import Database Penduduk (.xlsx format)
# Pastikan XAMPP/MySQL sudah running sebelum menjalankan script ini

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   IMPORT DATABASE PENDUDUK - FATUBESI" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$projectPath = "c:\Users\Lenovo\Documents\4 RPLK\TA\TA_FILE\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi"
$excelFile = "$projectPath\database_penduduk.xlsx"

# Cek apakah file Excel ada
if (!(Test-Path $excelFile)) {
    Write-Host "ERROR: File database_penduduk.xlsx tidak ditemukan!" -ForegroundColor Red
    Write-Host "Path: $excelFile" -ForegroundColor Yellow
    
    # Cek apakah file .xls ada
    $xlsFile = "$projectPath\database_penduduk.xls"
    if (Test-Path $xlsFile) {
        Write-Host "`nDitemukan file .xls, gunakan file tersebut sebagai gantinya? (Y/N)" -ForegroundColor Yellow
        $response = Read-Host
        if ($response -eq "Y" -or $response -eq "y") {
            $excelFile = $xlsFile
            Write-Host "Menggunakan: database_penduduk.xls" -ForegroundColor Green
        } else {
            exit 1
        }
    } else {
        exit 1
    }
}

Write-Host "[1/3] Cek koneksi database..." -ForegroundColor Yellow
cd $projectPath

# Test database connection
$dbTest = php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'OK'; } catch (Exception \$e) { echo 'FAIL: ' . \$e->getMessage(); }"

if ($dbTest -like "*FAIL*" -or $dbTest -like "*error*") {
    Write-Host "ERROR: Database tidak terhubung!" -ForegroundColor Red
    Write-Host "Pastikan MySQL/XAMPP sudah running!" -ForegroundColor Yellow
    Write-Host "Detail: $dbTest" -ForegroundColor Gray
    exit 1
}

Write-Host "✓ Database terhubung" -ForegroundColor Green
Write-Host ""

Write-Host "[2/3] Import data menggunakan Laravel Artisan Command..." -ForegroundColor Yellow
Write-Host "File: $excelFile" -ForegroundColor Cyan

# Create a simple Artisan command via tinker
Write-Host ""
Write-Host "Memproses import..." -ForegroundColor Yellow

$importCommand = @'
$file = new \Illuminate\Http\UploadedFile(
    '{0}',
    basename('{0}'),
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    null,
    true
);

$controller = new \App\Http\Controllers\PendudukController();
$request = new \Illuminate\Http\Request();
$request->files->set('file', $file);

try {
    $result = $controller->import($request);
    
    if (session()->has('success')) {
        echo "\n✓ IMPORT BERHASIL!\n";
        echo session()->get('success') . "\n";
    } elseif (session()->has('error')) {
        echo "\n✗ IMPORT GAGAL!\n";
        echo session()->get('error') . "\n";
    } else {
        echo "\n✓ Import selesai\n";
    }
} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
'@ -f $excelFile

# Save to temp file
$importCommand | Out-File -FilePath "$projectPath\temp_import_command.txt" -Encoding UTF8 -NoNewline

# Execute via tinker
php artisan tinker < temp_import_command.txt

# Clean up
Remove-Item "$projectPath\temp_import_command.txt" -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "[3/3] Verifikasi hasil import..." -ForegroundColor Yellow

# Check total data
$totalData = php artisan tinker --execute="echo DB::table('penduduks')->count();"
$totalData = $totalData -replace "[^0-9]", ""

if ($totalData -gt 0) {
    Write-Host "✓ Total data di database: $totalData baris" -ForegroundColor Green
    
    # Sample data
    Write-Host "`nSample 3 data pertama:" -ForegroundColor Cyan
    php artisan tinker --execute="DB::table('penduduks')->limit(3)->get()->each(function(\$p) { echo \$p->nik . ' - ' . \$p->nama . ' (' . \$p->dusun . '/' . \$p->rt . '/' . \$p->rw . ')' . PHP_EOL; });"
} else {
    Write-Host "WARNING: Tidak ada data yang berhasil diimport!" -ForegroundColor Yellow
    Write-Host "Coba periksa format Excel Anda atau gunakan import manual via browser." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "          IMPORT SELESAI!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Akses di browser: http://127.0.0.1:8000/penduduk" -ForegroundColor Green
Write-Host ""
