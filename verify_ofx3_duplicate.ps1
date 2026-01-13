# Direct file-by-file comparison
Write-Output "=== COMPARING ofx3 vs ofx2 DIRECTLY ==="
Write-Output ""

$ofx2Files = Get-ChildItem "lib\ofx2" -Recurse -File | Where-Object { $_.FullName -notlike "*\.git\*" }
$ofx3Files = Get-ChildItem "lib\ofx3" -Recurse -File | Where-Object { $_.FullName -notlike "*\.git\*" }

Write-Output "ofx2 file count: $($ofx2Files.Count)"
Write-Output "ofx3 file count: $($ofx3Files.Count)"
Write-Output ""

$identical = 0
$different = 0
$onlyInOfx2 = 0
$onlyInOfx3 = 0

foreach ($file2 in $ofx2Files) {
    $relPath = $file2.FullName.Replace((Resolve-Path "lib\ofx2").Path + "\", "")
    $file3Path = Join-Path "lib\ofx3" $relPath
    
    if (Test-Path $file3Path) {
        $hash2 = (Get-FileHash $file2.FullName -Algorithm MD5).Hash
        $hash3 = (Get-FileHash $file3Path -Algorithm MD5).Hash
        
        if ($hash2 -eq $hash3) {
            $identical++
        } else {
            $different++
            Write-Output "DIFFERENT: $relPath"
        }
    } else {
        $onlyInOfx2++
        Write-Output "ONLY IN ofx2: $relPath"
    }
}

# Check files only in ofx3
foreach ($file3 in $ofx3Files) {
    $relPath = $file3.FullName.Replace((Resolve-Path "lib\ofx3").Path + "\", "")
    $file2Path = Join-Path "lib\ofx2" $relPath
    
    if (-not (Test-Path $file2Path)) {
        $onlyInOfx3++
        Write-Output "ONLY IN ofx3: $relPath"
    }
}

Write-Output ""
Write-Output "=== RESULTS ==="
Write-Output "Identical files: $identical"
Write-Output "Different files: $different"
Write-Output "Only in ofx2: $onlyInOfx2"
Write-Output "Only in ofx3: $onlyInOfx3"
Write-Output ""

if ($identical -gt 0 -and $different -eq 0 -and $onlyInOfx2 -eq 0 -and $onlyInOfx3 -eq 0) {
    Write-Output "✅ VERDICT: ofx2 and ofx3 are IDENTICAL - safe to delete ofx3"
} elseif ($different -gt 0) {
    Write-Output "⚠️ VERDICT: ofx2 and ofx3 have DIFFERENCES - DO NOT delete yet!"
} else {
    Write-Output "⚠️ VERDICT: ofx2 and ofx3 have different files - review needed"
}
