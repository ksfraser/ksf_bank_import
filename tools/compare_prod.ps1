$root='c:\Users\prote\Documents\software-devel\ksf_bank_import'
$prod=Join-Path $root 'PROD'
if(-not (Test-Path $prod)){
    Write-Output 'PROD_NOT_FOUND'
    exit 0
}
$prodFiles = Get-ChildItem -Path $prod -Recurse -File | Where-Object {
    $p = $_.FullName
    -not ($p -match '\.php\.[0-9]{6,}') -and -not ($p -like '*~') -and -not ($p -like '*.swp') -and -not ($p -like '*.swo') -and -not ($p -match '\\vendor\\')
}
$diffs = @()
foreach($f in $prodFiles){
    $rel = $f.FullName.Substring($prod.Length+1).TrimStart('\')
    $repoPath = Join-Path $root $rel
    if(Test-Path $repoPath){
        $h1 = (Get-FileHash $f.FullName -Algorithm SHA256).Hash
        $h2 = (Get-FileHash $repoPath -Algorithm SHA256).Hash
        if($h1 -ne $h2){ $diffs += @{path=$rel; status='DIFF'} }
    } else { $diffs += @{path=$rel; status='NEW_IN_PROD'} }
}
# Also find files present in repo but missing in PROD
$repoFiles = Get-ChildItem -Path $root -Recurse -File | Where-Object {
    $p = $_.FullName
    -not ($p -match '\\.git\\') -and -not ($p -match '\\vendor\\') -and -not ($p -match '\\.gitignore')
}
foreach($f in $repoFiles){
    $rel = $f.FullName.Substring($root.Length+1).TrimStart('\')
    $prodPath = Join-Path $prod $rel
    if(-not (Test-Path $prodPath)){
        # ignore backup/tilde files
        if($rel -match '\.php\.[0-9]{6,}' -or $rel -like '*~' -or $rel -like '*.swp' -or $rel -like '*.swo') { continue }
        $diffs += @{path=$rel; status='MISSING_IN_PROD'}
    }
}
# Output
if($diffs.Count -eq 0){ Write-Output 'NO_DIFFS' ; exit 0 }
$diffs | Sort-Object status, path | ForEach-Object { "$($_.status) : $($_.path)" }
