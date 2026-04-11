$file = Join-Path $PSScriptRoot 'diffs_prod.txt'
if(-not (Test-Path $file)) { Write-Output 'NO_DIFFS_FILE'; exit 0 }
$lines = Get-Content $file
$diffs = $lines | Where-Object { $_ -match '^DIFF' }
$new = $lines | Where-Object { $_ -match '^NEW_IN_PROD' }
$miss = $lines | Where-Object { $_ -match '^MISSING_IN_PROD' }
Write-Output "DIFF_COUNT=$($diffs.Count)"
Write-Output "NEW_IN_PROD_COUNT=$($new.Count)"
Write-Output "MISSING_IN_PROD_COUNT=$($miss.Count)"
Write-Output ""
Write-Output "Sample DIFFs:" 
$diffs | Select-Object -First 20 | ForEach-Object { Write-Output $_ }
Write-Output ""
Write-Output "Sample NEW_IN_PROD:" 
$new | Select-Object -First 20 | ForEach-Object { Write-Output $_ }
Write-Output ""
Write-Output "Sample MISSING_IN_PROD:" 
$miss | Select-Object -First 20 | ForEach-Object { Write-Output $_ }
