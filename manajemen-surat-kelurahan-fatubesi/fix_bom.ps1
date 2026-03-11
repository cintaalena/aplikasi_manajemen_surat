$vendorSrc = "C:\Users\Lenovo\Documents\4 RPLK\TA\TA_FILE\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi\vendor\laravel\framework\src"
$bom = [byte[]](0xEF, 0xBB, 0xBF)
$fixed = 0
$files = Get-ChildItem $vendorSrc -Recurse -Filter "*.php"
Write-Host "Scanning $($files.Count) files..."
foreach ($file in $files) {
    $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        $newBytes = New-Object byte[] ($bytes.Length - 3)
        [System.Array]::Copy($bytes, 3, $newBytes, 0, $newBytes.Length)
        [System.IO.File]::WriteAllBytes($file.FullName, $newBytes)
        $fixed++
    }
}
Write-Host "Fixed BOM in $fixed files" -ForegroundColor Green
