# Side-by-side comparison helper for key files
# This will help identify specific code improvements to cherry-pick

$comparisons = @(
    @{
        Title = "Parser.php - Main parser logic"
        Your = "lib\ksf_ofxparser\src\Ksfraser\Parser.php"
        Jacques = "lib\jacques-ofxparser\lib\OfxParser\Parser.php"
    },
    @{
        Title = "Ofx.php - Main OFX object"
        Your = "lib\ksf_ofxparser\src\Ksfraser\Ofx.php"
        Jacques = "lib\jacques-ofxparser\lib\OfxParser\Ofx.php"
    },
    @{
        Title = "Transaction.php - Transaction entity"
        Your = "lib\ksf_ofxparser\src\Ksfraser\Entities\Transaction.php"
        Jacques = "lib\jacques-ofxparser\lib\OfxParser\Entities\Transaction.php"
    },
    @{
        Title = "Investment.php - Investment entity"
        Your = "lib\ksf_ofxparser\src\Ksfraser\Entities\Investment.php"
        Jacques = "lib\jacques-ofxparser\lib\OfxParser\Entities\Investment.php"
    }
)

Write-Output "=== FILE COMPARISON ANALYSIS ==="
Write-Output ""

foreach ($comp in $comparisons) {
    Write-Output "================================================"
    Write-Output $comp.Title
    Write-Output "================================================"
    
    if ((Test-Path $comp.Your) -and (Test-Path $comp.Jacques)) {
        # Get line counts
        $yourLines = (Get-Content $comp.Your).Count
        $jacquesLines = (Get-Content $comp.Jacques).Count
        
        Write-Output "Your file: $yourLines lines"
        Write-Output "Jacques file: $jacquesLines lines"
        Write-Output "Difference: $($jacquesLines - $yourLines) lines"
        
        # Check for strict types
        $yourHasStrict = (Get-Content $comp.Your -Raw) -match "declare\(strict_types=1\)"
        $jacquesHasStrict = (Get-Content $comp.Jacques -Raw) -match "declare\(strict_types=1\)"
        
        Write-Output ""
        Write-Output "Strict types declaration:"
        Write-Output "  Your file: $(if ($yourHasStrict) { 'YES' } else { 'NO' })"
        Write-Output "  Jacques file: $(if ($jacquesHasStrict) { 'YES' } else { 'NO' })"
        
        # Check for return type hints
        $yourHasReturnTypes = (Select-String -Path $comp.Your -Pattern ": \??(string|int|bool|array|float)").Count
        $jacquesHasReturnTypes = (Select-String -Path $comp.Jacques -Pattern ": \??(string|int|bool|array|float)").Count
        
        Write-Output ""
        Write-Output "Return type hints:"
        Write-Output "  Your file: $yourHasReturnTypes occurrences"
        Write-Output "  Jacques file: $jacquesHasReturnTypes occurrences"
        
        Write-Output ""
        Write-Output "To compare manually:"
        Write-Output "  code --diff `"$($comp.Your)`" `"$($comp.Jacques)`""
        Write-Output ""
        
    } else {
        Write-Output "ERROR: One or both files not found"
        Write-Output "  Your file exists: $(Test-Path $comp.Your)"
        Write-Output "  Jacques file exists: $(Test-Path $comp.Jacques)"
    }
    Write-Output ""
}

Write-Output "================================================"
Write-Output "SUMMARY"
Write-Output "================================================"
Write-Output ""
Write-Output "To open all comparisons in VS Code:"
Write-Output ""
foreach ($comp in $comparisons) {
    if ((Test-Path $comp.Your) -and (Test-Path $comp.Jacques)) {
        Write-Output "code --diff `"$($comp.Your)`" `"$($comp.Jacques)`""
    }
}
