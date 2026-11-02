# Copy unique Ksfraser HTML files from repo to external library
# Generated: 2026-04-11
# Usage: Run from PowerShell as needed. This script WILL NOT overwrite existing files in the external library.

$repoRoot = "c:\Users\prote\Documents\software-devel\ksf_bank_import\src\Ksfraser\HTML"
$extRoot  = "c:\Users\prote\Documents\software-devel\html\src\Ksfraser\HTML"
$listFile = "$PSScriptRoot\unique_repo_only_files.txt"

if (-not (Test-Path $listFile)) {
    Write-Error "List file not found: $listFile"
    exit 1
}

$lines = Get-Content $listFile | Where-Object { $_ -and -not $_.StartsWith('#') }

foreach ($rel in $lines) {
    $src = Join-Path $repoRoot $rel
    $dst = Join-Path $extRoot $rel

    if (-not (Test-Path $src)) {
        Write-Warning "Source missing: $src"
        continue
    }

    $dstDir = Split-Path $dst -Parent
    if (-not (Test-Path $dstDir)) { New-Item -ItemType Directory -Path $dstDir -Force | Out-Null }

    if (Test-Path $dst) {
        Write-Output "SKIP (exists): $rel"
        # If you want to create backups instead of skipping, uncomment below:
        # $bak = "$dst.bak.$((Get-Date).ToString('yyyyMMdd-HHmmss'))"
        # Copy-Item -Path $dst -Destination $bak -Force
        continue
    }

    Copy-Item -Path $src -Destination $dst -Force
    Write-Output "COPIED: $rel"
}

Write-Output "Done. Review files in $extRoot before committing."
