# Compare Jacques vs KSF file by file
$jacquesBase = "lib\jacques-ofxparser\lib\OfxParser"
$ksfBase = "lib\ksf_ofxparser\src\Ksfraser"

# Common files to compare
$commonFiles = @(
    "Parser.php",
    "Ofx.php",
    "Utils.php",
    "Entities\AbstractEntity.php",
    "Entities\AccountInfo.php",
    "Entities\BankAccount.php",
    "Entities\Statement.php",
    "Entities\Transaction.php",
    "Entities\SignOn.php",
    "Entities\Status.php",
    "Entities\Institute.php",
    "Entities\Inspectable.php",
    "Entities\OfxLoadable.php",
    "Entities\Investment.php",
    "Entities\Investment\Account.php",
    "Ofx\Investment.php",
    "Parsers\Investment.php"
)

foreach ($file in $commonFiles) {
    $jacquesFile = Join-Path $jacquesBase $file
    $ksfFile = Join-Path $ksfBase $file
    
    if ((Test-Path $jacquesFile) -and (Test-Path $ksfFile)) {
        $jacquesLines = (Get-Content $jacquesFile | Measure-Object -Line).Lines
        $ksfLines = (Get-Content $ksfFile | Measure-Object -Line).Lines
        
        Write-Output "=== $file ==="
        Write-Output "Jacques: $jacquesLines lines"
        Write-Output "KSF: $ksfLines lines"
        Write-Output "Difference: $($ksfLines - $jacquesLines) lines"
        Write-Output ""
    }
}
