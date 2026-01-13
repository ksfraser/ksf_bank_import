# Delete only the identical files from ofx3
Write-Output "Deleting 14 identical files from ofx3..."

$ofx2Files = Get-ChildItem "lib\ofx2" -Recurse -File | Where-Object { $_.FullName -notlike "*\.git\*" }
$deleted = 0

foreach ($file2 in $ofx2Files) {
    $relPath = $file2.FullName.Replace((Resolve-Path "lib\ofx2").Path + "\", "")
    $file3Path = Join-Path "lib\ofx3" $relPath
    
    if (Test-Path $file3Path) {
        $hash2 = (Get-FileHash $file2.FullName -Algorithm MD5).Hash
        $hash3 = (Get-FileHash $file3Path -Algorithm MD5).Hash
        
        if ($hash2 -eq $hash3) {
            Remove-Item $file3Path -Force
            Write-Output "Deleted: $relPath"
            $deleted++
        }
    }
}

Write-Output ""
Write-Output "Deleted $deleted identical files from ofx3"
Write-Output ""
Write-Output "Remaining files in ofx3:"
Get-ChildItem "lib\ofx3" -Recurse -File | ForEach-Object { 
    $_.FullName.Replace((Resolve-Path "lib\ofx3").Path + "\", "")
}
