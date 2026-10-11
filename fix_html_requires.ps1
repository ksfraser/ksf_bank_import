$dirs = @(
    "c:\Users\prote\Documents\software-devel\ksf_bank_import\src",
    "c:\Users\prote\Documents\software-devel\ksf_bank_import\views",
    "c:\Users\prote\Documents\software-devel\ksf_bank_import\tests"
)
$count = 0
foreach ($dir in $dirs) {
    Get-ChildItem $dir -Recurse -Filter "*.php" | ForEach-Object {
        $file = $_.FullName
        $content = Get-Content $file -Raw
        $newContent = $content -replace "(?m)^require_once\s*\(?\s*__DIR__\s*\.\s*['\"][^'\"]*?/HTML/Html[^\n]+\n?", ""
        if ($newContent -ne $content) {
            Set-Content -Path $file -Value $newContent -NoNewline
            Write-Host "Cleaned: $($_.Name)"
            $count++
        }
    }
}
Write-Host "`nTotal files cleaned: $count"
