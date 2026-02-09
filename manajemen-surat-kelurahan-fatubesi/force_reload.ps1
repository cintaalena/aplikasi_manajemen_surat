# Force Browser Reload Script
Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "  CARA PASTI UNTUK FIX MASALAH INI" -ForegroundColor Yellow
Write-Host "============================================`n" -ForegroundColor Cyan

Write-Host "OPSI 1 - INCOGNITO WINDOW (PALING MUDAH):" -ForegroundColor Green
Write-Host "1. Buka browser baru (Chrome/Edge/Firefox)" -ForegroundColor White
Write-Host "2. Tekan Ctrl + Shift + N (untuk Incognito)" -ForegroundColor White
Write-Host "3. Ketik: http://127.0.0.1:8000" -ForegroundColor Yellow
Write-Host "4. Login dengan akun Anda" -ForegroundColor White
Write-Host "5. Klik menu 'Database Penduduk'" -ForegroundColor White
Write-Host "   -> Sekarang akan muncul console log 'Navigating to penduduk...'" -ForegroundColor Gray
Write-Host "`n" -ForegroundColor White

Write-Host "OPSI 2 - CLEAR BROWSER CACHE:" -ForegroundColor Green
Write-Host "1. Di browser yang sudah terbuka, tekan Ctrl + Shift + Delete" -ForegroundColor White
Write-Host "2. Pilih 'Time range: All time'" -ForegroundColor White
Write-Host "3. Centang:" -ForegroundColor White
Write-Host "   [x] Cookies and other site data" -ForegroundColor Gray
Write-Host "   [x] Cached images and files" -ForegroundColor Gray
Write-Host "4. Klik 'Clear data'" -ForegroundColor White
Write-Host "5. Tutup tab, buka tab baru" -ForegroundColor White
Write-Host "6. Akses: http://127.0.0.1:8000" -ForegroundColor Yellow
Write-Host "`n" -ForegroundColor White

Write-Host "OPSI 3 - DEVELOPER TOOLS (UNTUK DEVELOPER):" -ForegroundColor Green
Write-Host "1. Tekan F12 untuk buka Developer Tools" -ForegroundColor White
Write-Host "2. Klik kanan pada tombol Refresh di browser" -ForegroundColor White
Write-Host "3. Pilih 'Empty Cache and Hard Reload'" -ForegroundColor White
Write-Host "4. Klik menu 'Database Penduduk'" -ForegroundColor White
Write-Host "5. Lihat tab 'Console' - seharusnya muncul:" -ForegroundColor White
Write-Host "   'Navigating to penduduk...'" -ForegroundColor Gray
Write-Host "`n" -ForegroundColor White

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  PERUBAHAN YANG SUDAH DILAKUKAN:" -ForegroundColor Yellow
Write-Host "============================================`n" -ForegroundColor Cyan
Write-Host "[x] Mengganti Link component dengan <a> tag" -ForegroundColor Green
Write-Host "[x] Menambahkan debug logging di console" -ForegroundColor Green
Write-Host "[x] Menggunakan router.visit() langsung" -ForegroundColor Green
Write-Host "[x] Asset sudah di-rebuild (npm run build)" -ForegroundColor Green
Write-Host "`n" -ForegroundColor White

Write-Host "Catatan: Error 409 Conflict HANYA terjadi karena browser" -ForegroundColor Yellow
Write-Host "menyimpan JavaScript lama. Setelah Anda dapat versi baru," -ForegroundColor Yellow
Write-Host "menu akan berfungsi normal." -ForegroundColor Yellow
Write-Host "`n" -ForegroundColor White

# Test endpoint
Write-Host "Testing endpoint..." -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/penduduk" -UseBasicParsing -TimeoutSec 5
    Write-Host "[OK] Endpoint /penduduk: Status $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Endpoint gagal: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n============================================`n" -ForegroundColor Cyan
