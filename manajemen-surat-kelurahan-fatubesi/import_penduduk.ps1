# Script Import Database Penduduk
# Pastikan XAMPP/MySQL sudah running sebelum menjalankan script ini

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   IMPORT DATABASE PENDUDUK - FATUBESI" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$projectPath = "c:\Users\Lenovo\Documents\4 RPLK\TA\TA_FILE\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi"
$excelFile = "$projectPath\database_penduduk.xls"

# Cek apakah file Excel ada
if (!(Test-Path $excelFile)) {
    Write-Host "ERROR: File database_penduduk.xls tidak ditemukan!" -ForegroundColor Red
    Write-Host "Path: $excelFile" -ForegroundColor Yellow
    exit 1
}

Write-Host "[1/4] Cek koneksi database..." -ForegroundColor Yellow
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

Write-Host "[2/4] Jalankan migration database..." -ForegroundColor Yellow
$migrationOutput = php artisan migrate --force 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Migration berhasil" -ForegroundColor Green
} else {
    Write-Host "WARNING: Migration mungkin sudah dijalankan sebelumnya" -ForegroundColor Yellow
    Write-Host "Detail: $migrationOutput" -ForegroundColor Gray
}

Write-Host ""
Write-Host "[3/4] Konversi Excel ke CSV..." -ForegroundColor Yellow

# Convert XLS to CSV using PHP script
$convertScript = @'
<?php
require 'vendor/autoload.php';
use Shuchkin\SimpleXLSX;

$xlsx = SimpleXLSX::parse('database_penduduk.xls');
if (!$xlsx) {
    die("ERROR: " . SimpleXLSX::parseError());
}

$rows = $xlsx->rows();
$csvFile = 'database_penduduk_converted.csv';
$fp = fopen($csvFile, 'w');

foreach ($rows as $row) {
    fputcsv($fp, $row);
}

fclose($fp);
echo "CSV created: $csvFile";
'@

$convertScript | Out-File -FilePath "$projectPath\temp_convert.php" -Encoding UTF8
$convertResult = php temp_convert.php 2>&1

if ($convertResult -like "*CSV created*") {
    Write-Host "✓ File berhasil dikonversi ke CSV" -ForegroundColor Green
    Remove-Item "temp_convert.php" -Force
} else {
    Write-Host "ERROR: Gagal konversi file" -ForegroundColor Red
    Write-Host "Detail: $convertResult" -ForegroundColor Gray
    exit 1
}

Write-Host ""
Write-Host "[4/4] Import data ke database..." -ForegroundColor Yellow
Write-Host "File: database_penduduk.xls" -ForegroundColor Gray
Write-Host ""

# Buat script PHP untuk import
$importScript = @'
<?php
require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Penduduk;
use Illuminate\Support\Facades\DB;
use Shuchkin\SimpleXLSX;

$xlsx = SimpleXLSX::parse('database_penduduk.xls');
if (!$xlsx) {
    die("ERROR: " . SimpleXLSX::parseError());
}

$rows = $xlsx->rows();
$header = array_shift($rows);

echo "Header: " . implode(", ", $header) . "\n";
echo "Total rows: " . count($rows) . "\n\n";

$inserted = 0;
$updated = 0;
$skipped = 0;

DB::beginTransaction();

try {
    foreach ($rows as $index => $row) {
        // Skip empty rows
        if (empty(array_filter($row))) {
            $skipped++;
            continue;
        }
        
        // Map data (sesuaikan dengan struktur file Anda)
        // Ini contoh mapping, sesuaikan dengan header file Anda
        $data = [];
        foreach ($header as $i => $col) {
            $data[strtolower(trim($col))] = $row[$i] ?? '';
        }
        
        // Minimal validation
        if (empty($data['nama']) || empty($data['kode_keluarga'])) {
            $skipped++;
            continue;
        }
        
        // Check existing
        $existing = Penduduk::where('kode_keluarga', $data['kode_keluarga'] ?? '')
            ->where('nama', $data['nama'] ?? '')
            ->first();
            
        if ($existing) {
            $existing->update($data);
            $updated++;
        } else {
            Penduduk::create($data);
            $inserted++;
        }
        
        if (($inserted + $updated) % 100 == 0) {
            echo "Progress: " . ($inserted + $updated) . " rows processed...\n";
        }
    }
    
    DB::commit();
    
    echo "\n========================================\n";
    echo "IMPORT SELESAI!\n";
    echo "========================================\n";
    echo "✓ Berhasil insert: $inserted\n";
    echo "✓ Berhasil update: $updated\n";
    echo "- Dilewati: $skipped\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\nERROR: " . $e->getMessage() . "\n";
    exit(1);
}
'@

$importScript | Out-File -FilePath "$projectPath\temp_import.php" -Encoding UTF8
php temp_import.php

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✓ IMPORT BERHASIL!" -ForegroundColor Green
    Remove-Item "temp_import.php" -Force -ErrorAction SilentlyContinue
    Remove-Item "database_penduduk_converted.csv" -Force -ErrorAction SilentlyContinue
} else {
    Write-Host ""
    Write-Host "ERROR: Import gagal" -ForegroundColor Red
}

Write-Host ""
Write-Host "Selesai! Silakan buka halaman Database Penduduk di browser untuk melihat data." -ForegroundColor Cyan
Write-Host "URL: http://127.0.0.1:8000/penduduk" -ForegroundColor Cyan
Write-Host ""
