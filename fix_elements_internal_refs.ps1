# Fix internal references within Elements directory
# Elements should reference other Elements using Elements namespace

$files = Get-ChildItem -Path "src/Ksfraser/HTML/Elements" -Filter "*.php"

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $originalContent = $content
    
    # Fix references to other Html* classes within Elements (but not HtmlElement, HtmlElementInterface, HtmlEmptyElement which are in root)
    # Match: use Ksfraser\HTML\Html[specific class]; but exclude the base classes
    $content = $content -replace "use Ksfraser\\HTML\\(Html[A-Z][a-z]+[A-Z][a-zA-Z]*);", "use Ksfraser\HTML\Elements\`$1;"
    $content = $content -replace "use Ksfraser\\HTML\\(HtmlInput[A-Z][a-zA-Z]*);", "use Ksfraser\HTML\Elements\`$1;"
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -NoNewline
        Write-Host "  Updated: $($file.Name)"
    }
}

Write-Host "Done!"
