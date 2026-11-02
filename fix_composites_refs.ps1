# Final comprehensive fix for all HTML namespace references

$files = Get-ChildItem -Path . -Include *.php -Recurse -File | Where-Object { 
    $_.FullName -notmatch '\\vendor\\' -and 
    $_.FullName -notmatch '\\fix_.*\.ps1$'
}

$fixes = 0

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $originalContent = $content
    
    # Fix LabelRowBase - it's in Composites now
    $content = $content -replace "use Ksfraser\\HTML\\LabelRowBase;", "use Ksfraser\HTML\Composites\LabelRowBase;"
    $content = $content -replace "use Ksfraser\\Html\\LabelRowBase;", "use Ksfraser\HTML\Composites\LabelRowBase;"
    $content = $content -replace "require_once\(__DIR__\s*\.\s*'/../../HTML/LabelRowBase\.php'\s*\);", "require_once(__DIR__ . '/../../HTML/Composites/LabelRowBase.php');"
    
    # Fix HtmlLabelRow - it's in Composites now
    $content = $content -replace "use Ksfraser\\HTML\\HtmlLabelRow;", "use Ksfraser\HTML\Composites\HtmlLabelRow;"
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -NoNewline
        Write-Host "Fixed: $($file.Name)"
        $fixes++
    }
}

Write-Host "Done! Fixed $fixes files."
