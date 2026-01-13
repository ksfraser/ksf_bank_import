$repos = @("adelarcubs-ofxparser", "jacques-ofxparser", "memhetcoban-ofxparser", "ofx1", "ofx2", "ofx3")
$base = "lib\ofx4"

foreach ($repo in $repos) {
    $compareDir = "lib\$repo"
    if (-not (Test-Path $compareDir)) { 
        Write-Output "`n=== $repo : DOES NOT EXIST ==="
        continue 
    }
    
    Write-Output "`n=== Comparing $repo to ofx4 (baseline) ==="
    $identical = 0
    $different = 0
    $missing = 0
    
    Get-ChildItem -Path $base -Recurse -File | Where-Object {
        $_.FullName -notlike "*\.git\*" -and $_.FullName -notlike "*\vendor\*"
    } | ForEach-Object {
        $relPath = $_.FullName.Substring((Resolve-Path $base).Path.Length + 1)
        $comparePath = Join-Path $compareDir $relPath
        
        if (Test-Path $comparePath) {
            $hash1 = (Get-FileHash $_.FullName -Algorithm MD5).Hash
            $hash2 = (Get-FileHash $comparePath -Algorithm MD5).Hash
            if ($hash1 -eq $hash2) { 
                $identical++ 
            } else { 
                $different++ 
            }
        } else {
            $missing++
        }
    }
    
    Write-Output "Identical files: $identical"
    Write-Output "Different files: $different"
    Write-Output "Missing in fork: $missing"
    Write-Output "Total in baseline: $($identical + $different + $missing)"
}
