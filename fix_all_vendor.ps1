$vendorDir = "C:\Users\Lenovo\Documents\4 RPLK\TA\TA_FILE\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi\manajemen-surat-kelurahan-fatubesi\vendor"
$cacheDir = "$env:LOCALAPPDATA\Composer\files"
$bom = [byte[]](0xEF, 0xBB, 0xBF)
$restoredTotal = 0
$fixedTotal = 0

# Get all installed packages from composer
$installedJson = Get-Content "$vendorDir\composer\installed.json" | ConvertFrom-Json
$packages = $installedJson.packages

Write-Host "Processing $($packages.Count) packages..."

foreach ($pkg in $packages) {
    $pkgName = $pkg.name  # e.g. "nesbot/carbon"
    $pkgDist = $pkg.dist
    if (-not $pkgDist -or -not $pkgDist.reference) { continue }

    $parts = $pkgName -split "/"
    $vendor = $parts[0]
    $name = $parts[1]

    # Find cached zip for this package
    $cachePath = "$cacheDir\$vendor\$name"
    if (-not (Test-Path $cachePath)) { continue }
    $zipFile = Get-ChildItem $cachePath -Filter "*.zip" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if (-not $zipFile) { continue }

    $pkgVendorDir = "$vendorDir\$($pkgName -replace '/', '\')"
    if (-not (Test-Path $pkgVendorDir)) { continue }

    try {
        Add-Type -AssemblyName System.IO.Compression.FileSystem -ErrorAction SilentlyContinue
        $zip = [System.IO.Compression.ZipFile]::OpenRead($zipFile.FullName)

        # Detect prefix
        $sampleEntry = $zip.Entries | Where-Object { $_.FullName -notmatch "/$" } | Select-Object -First 1
        if (-not $sampleEntry) { $zip.Dispose(); continue }
        $prefix = ($sampleEntry.FullName -split "/")[0] + "/"

        $restored = 0
        foreach ($entry in $zip.Entries) {
            if ($entry.FullName.StartsWith($prefix) -and $entry.Length -gt 0 -and -not $entry.FullName.EndsWith("/")) {
                $relPath = $entry.FullName.Substring($prefix.Length) -replace "/","\"
                $destPath = Join-Path $pkgVendorDir $relPath
                if (-not (Test-Path $destPath)) {
                    $destDir = Split-Path $destPath
                    if (-not (Test-Path $destDir)) { New-Item -ItemType Directory -Path $destDir -Force | Out-Null }
                    $stream = $entry.Open()
                    $bytes = New-Object byte[] ([int]$entry.Length)
                    $stream.Read($bytes, 0, $bytes.Length) | Out-Null
                    $stream.Close()
                    [System.IO.File]::WriteAllBytes($destPath, $bytes)
                    $restored++
                    $restoredTotal++
                }
            }
        }
        $zip.Dispose()
        if ($restored -gt 0) { Write-Host "  $pkgName : restored $restored files" -ForegroundColor Yellow }
    } catch {
        Write-Host "  ERROR with $pkgName : $_" -ForegroundColor Red
    }
}

# Fix BOM in ALL vendor PHP files
Write-Host ""
Write-Host "Fixing BOM in all vendor PHP files..."
$allPhp = Get-ChildItem $vendorDir -Recurse -Filter "*.php"
Write-Host "Scanning $($allPhp.Count) files..."
foreach ($file in $allPhp) {
    try {
        $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
        if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
            $newBytes = New-Object byte[] ($bytes.Length - 3)
            [System.Array]::Copy($bytes, 3, $newBytes, 0, $newBytes.Length)
            [System.IO.File]::WriteAllBytes($file.FullName, $newBytes)
            $fixedTotal++
        }
    } catch {}
}

Write-Host ""
Write-Host "=== COMPLETE ===" -ForegroundColor Green
Write-Host "Restored: $restoredTotal missing files" -ForegroundColor Green
Write-Host "Fixed BOM: $fixedTotal files" -ForegroundColor Green
