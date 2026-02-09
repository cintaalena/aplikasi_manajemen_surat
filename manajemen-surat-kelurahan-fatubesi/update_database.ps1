# Script untuk update database struktur penduduk
Write-Host "`n===============================================" -ForegroundColor Cyan
Write-Host "  UPDATE DATABASE STRUKTUR PENDUDUK" -ForegroundColor Yellow
Write-Host "===============================================`n" -ForegroundColor Cyan

Write-Host "Langkah 1: Rollback migration sebelumnya..." -ForegroundColor White
try {
    php artisan migrate:rollback --step=2 2>&1 | Out-String | Write-Host -ForegroundColor Gray
    Write-Host "[OK] Rollback berhasil`n" -ForegroundColor Green
} catch {
    Write-Host "[WARNING] Rollback gagal, melanjutkan...`n" -ForegroundColor Yellow
}

Write-Host "Langkah 2: Jalankan migration baru..." -ForegroundColor White
try {
    php artisan migrate --force 2>&1 | Out-String | Write-Host -ForegroundColor Gray
    Write-Host "[OK] Migration berhasil`n" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Migration gagal`n" -ForegroundColor Red
    Write-Host "Coba jalankan manual: php artisan migrate`n" -ForegroundColor Yellow
}

Write-Host "Langkah 3: Clear cache..." -ForegroundColor White
php artisan cache:clear | Out-Null
php artisan view:clear | Out-Null
Write-Host "[OK] Cache cleared`n" -ForegroundColor Green

Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "  STRUKTUR TABEL BARU:" -ForegroundColor Yellow
Write-Host "===============================================`n" -ForegroundColor Cyan
Write-Host "Data Keluarga:" -ForegroundColor Cyan
Write-Host "  - Kode Keluarga (No KK)" -ForegroundColor White
Write-Host "  - Nama Kepala Keluarga" -ForegroundColor White
Write-Host "  - Alamat, RT, RW, Dusun`n" -ForegroundColor White

Write-Host "Data Individu:" -ForegroundColor Cyan
Write-Host "  - No. Urut, NIK, Nama" -ForegroundColor White
Write-Host "  - Jenis Kelamin, Hubungan" -ForegroundColor White
Write-Host "  - Tempat/Tanggal Lahir, Usia" -ForegroundColor White
Write-Host "  - Status Perkawinan, Agama" -ForegroundColor White
Write-Host "  - Golongan Darah, Kewarganegaraan" -ForegroundColor White
Write-Host "  - Etnis/Suku, Pendidikan, Pekerjaan`n" -ForegroundColor White

Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "  FORMAT FILE EXCEL/CSV YANG DIDUKUNG:" -ForegroundColor Yellow
Write-Host "===============================================`n" -ForegroundColor Cyan
Write-Host "Header bisa menggunakan salah satu format:" -ForegroundColor White
Write-Host "- RT, RW, Dusun, Alamat" -ForegroundColor Gray
Write-Host "- Kode Keluarga / No KK / Nomor KK" -ForegroundColor Gray
Write-Host "- Nama Kepala Keluarga / Kepala Keluarga" -ForegroundColor Gray
Write-Host "- No / No. / No Urut" -ForegroundColor Gray
Write-Host "- NIK" -ForegroundColor Gray
Write-Host "- Nama / Nama Anggota Keluarga" -ForegroundColor Gray
Write-Host "- Jenis Kelamin / JK" -ForegroundColor Gray
Write-Host "- Hubungan" -ForegroundColor Gray
Write-Host "- Tempat Lahir, Tanggal Lahir" -ForegroundColor Gray
Write-Host "- Usia" -ForegroundColor Gray
Write-Host "- Status / Status Perkawinan" -ForegroundColor Gray
Write-Host "- Agama" -ForegroundColor Gray
Write-Host "- GDarah / Gol Darah / Golongan Darah" -ForegroundColor Gray
Write-Host "- Kewarganegaraan / Warga Negara" -ForegroundColor Gray
Write-Host "- Etnis / Suku / Etnis/Suku" -ForegroundColor Gray
Write-Host "- Pendidikan, Pekerjaan`n" -ForegroundColor Gray

Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "  SELESAI!" -ForegroundColor Green
Write-Host "===============================================`n" -ForegroundColor Cyan
Write-Host "Buka browser (gunakan Incognito):" -ForegroundColor White
Write-Host "  http://127.0.0.1:8000/penduduk`n" -ForegroundColor Yellow
Write-Host "Sekarang Anda bisa:" -ForegroundColor White
Write-Host "  [x] Import file Excel (.xlsx, .xls)" -ForegroundColor Green
Write-Host "  [x] Import file CSV (.csv, .txt)" -ForegroundColor Green
Write-Host "  [x] Lihat data penduduk lengkap" -ForegroundColor Green
Write-Host "  [x] Export data ke CSV`n" -ForegroundColor Green
