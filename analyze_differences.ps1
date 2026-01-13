# Detailed comparison of OFX parser forks
# Baseline: lib/ofx4 (asgrim's original repo)

$repos = @("jacques-ofxparser", "memhetcoban-ofxparser", "ofx2", "ofx3")
$base = "lib\ofx4"

foreach ($repo in $repos) {
    $compareDir = "lib\$repo"
    
    Write-Output "`n========================================="
    Write-Output "ANALYZING: $repo"
    Write-Output "=========================================`n"
    
    # Get different files
    Write-Output "DIFFERENT FILES (Modified from baseline):"
    Write-Output "-----------------------------------------"
    
    Get-ChildItem -Path $base -Recurse -File | Where-Object {
        $_.FullName -notlike "*\.git\*" -and $_.FullName -notlike "*\vendor\*"
    } | ForEach-Object {
        $relPath = $_.FullName.Substring((Resolve-Path $base).Path.Length + 1)
        $comparePath = Join-Path $compareDir $relPath
        
        if (Test-Path $comparePath) {
            $hash1 = (Get-FileHash $_.FullName -Algorithm MD5).Hash
            $hash2 = (Get-FileHash $comparePath -Algorithm MD5).Hash
            if ($hash1 -ne $hash2) {
                Write-Output "  $relPath"
            }
        }
    } | Select-Object -First 30
    
    Write-Output "`nMISSING FILES (Not in fork):"
    Write-Output "-----------------------------"
    
    Get-ChildItem -Path $base -Recurse -File | Where-Object {
        $_.FullName -notlike "*\.git\*" -and $_.FullName -notlike "*\vendor\*"
    } | ForEach-Object {
        $relPath = $_.FullName.Substring((Resolve-Path $base).Path.Length + 1)
        $comparePath = Join-Path $compareDir $relPath
        
        if (-not (Test-Path $comparePath)) {
            Write-Output "  $relPath"
        }
    } | Select-Object -First 20
    
    Write-Output "`n"
}
