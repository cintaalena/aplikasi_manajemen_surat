Add-Type -AssemblyName System.IO.Compression.FileSystem

# Find the most recent laravel/framework zip in composer cache
$cacheDir = "$env:LOCALAPPDATA\Composer\files\laravel\framework"
$zipFile = Get-ChildItem $cacheDir -Filter "*.zip" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
Write-Host "Using zip: $($zipFile.FullName)"

$zip = [System.IO.Compression.ZipFile]::OpenRead($zipFile.FullName)

# Detect prefix (folder name inside zip)
$prefix = ($zip.Entries | Where-Object { $_.FullName -like "*/src/Illuminate/Foundation/Application.php" } | Select-Object -First 1).FullName
$prefix = $prefix -replace "src/Illuminate.*", "src/"
Write-Host "Zip prefix: $prefix"

$baseDir = Split-Path $MyInvocation.MyCommand.Path
$vendorSrc = Join-Path $baseDir "vendor\laravel\framework\src"

$restored = 0
$skipped = 0

foreach ($entry in $zip.Entries) {
    if ($entry.FullName.StartsWith($prefix) -and $entry.Length -gt 0 -and $entry.FullName.EndsWith(".php")) {
        $relPath = $entry.FullName.Substring($prefix.Length) -replace "/","\"
        $destPath = Join-Path $vendorSrc $relPath
        if (-not (Test-Path $destPath)) {
            $destDir = Split-Path $destPath
            if (-not (Test-Path $destDir)) { New-Item -ItemType Directory -Path $destDir -Force | Out-Null }
            $stream = $entry.Open()
            $bytes = New-Object byte[] $entry.Length
            $totalRead = 0
            while ($totalRead -lt $entry.Length) {
                $read = $stream.Read($bytes, $totalRead, $entry.Length - $totalRead)
                if ($read -eq 0) { break }
                $totalRead += $read
            }
            $stream.Close()
            [System.IO.File]::WriteAllBytes($destPath, $bytes)
            Write-Host "  RESTORED: $relPath"
            $restored++
        } else {
            $skipped++
        }
    }
}

$zip.Dispose()
Write-Host ""
Write-Host "=== DONE: Restored $restored files, Skipped $skipped existing files ===" -ForegroundColor Green
