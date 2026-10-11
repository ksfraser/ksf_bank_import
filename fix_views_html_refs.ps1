# Update all Views/HTML/ references to src/Ksfraser/HTML/

$files = Get-ChildItem -Path . -Include *.php -Recurse -File | Where-Object { 
    $_.FullName -notmatch '\\vendor\\' -and
    $_.FullName -notmatch '\\.corrupted' -and
    $_.FullName -notmatch '\\.prod' -and
    $_.FullName -notmatch '\\.20250607' -and
    $_.FullName -notmatch '\\.bak' -and
    $_.FullName -notmatch '~$'
}

$fixes = 0

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $originalContent = $content
    
    # Update Views/HTML/ paths to src/Ksfraser/HTML/
    # Base classes (root)
    $content = $content -replace "require_once\(\s*__DIR__\s*\.\s*'/Views/HTML/HtmlElementInterface\.php'\s*\);", "require_once(__DIR__ . '/src/Ksfraser/HTML/HtmlElementInterface.php');"
    $content = $content -replace "require_once\(\s*__DIR__\s*\.\s*'/Views/HTML/HtmlElement\.php'\s*\);", "require_once(__DIR__ . '/src/Ksfraser/HTML/HtmlElement.php');"
    $content = $content -replace "require_once\(\s*__DIR__\s*\.\s*'/Views/HTML/HtmlAttribute\.php'\s*\);", "require_once(__DIR__ . '/src/Ksfraser/HTML/HtmlAttribute.php');"
    
    # Elements
    $content = $content -replace "require_once\s+__DIR__\s*\.\s*'/Views/HTML/(Html[A-Z][a-zA-Z]+)\.php'", "require_once __DIR__ . '/src/Ksfraser/HTML/Elements/`$1.php'"
    $content = $content -replace "require_once\(\s*__DIR__\s*\.\s*'/Views/HTML/(Html[A-Z][a-zA-Z]+)\.php'\s*\)", "require_once(__DIR__ . '/src/Ksfraser/HTML/Elements/`$1.php')"
    
    # Relative paths for tests (../../Views/HTML/)
    $content = $content -replace "require_once\s+__DIR__\s*\.\s*'/../../Views/HTML/HtmlElementInterface\.php'", "require_once __DIR__ . '/../../src/Ksfraser/HTML/HtmlElementInterface.php'"
    $content = $content -replace "require_once\s+__DIR__\s*\.\s*'/../../Views/HTML/HtmlElement\.php'", "require_once __DIR__ . '/../../src/Ksfraser/HTML/HtmlElement.php'"
    $content = $content -replace "require_once\s+__DIR__\s*\.\s*'/../../Views/HTML/HtmlAttribute\.php'", "require_once __DIR__ . '/../../src/Ksfraser/HTML/HtmlAttribute.php'"
    $content = $content -replace "require_once\s+__DIR__\s*\.\s*'/../../Views/HTML/(Html[A-Z][a-zA-Z]+)\.php'", "require_once __DIR__ . '/../../src/Ksfraser/HTML/Elements/`$1.php'"
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -NoNewline
        Write-Host "Fixed: $($file.Name)"
        $fixes++
    }
}

Write-Host "Done! Fixed $fixes files."
