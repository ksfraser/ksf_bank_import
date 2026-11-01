# Compare features across OFX parser repos

$repos = @{
    "ksf_ofxparser" = "lib\ksf_ofxparser\src\Ksfraser"
    "jacques" = "lib\jacques-ofxparser\lib\OfxParser"
    "ofx2" = "lib\ofx2\lib\OfxParser"
    "ofx4_baseline" = "lib\ofx4\lib\OfxParser"
    "memhetcoban" = "lib\memhetcoban-ofxparser\lib\OfxParser"
}

Write-Output "=== FEATURE COMPARISON ==="
Write-Output ""

# Check for key features
$features = @{
    "Credit Card Support" = "Entities\CreditCardAccount*.php"
    "Investment Support" = "Entities\Investment*.php"
    "Banking Account" = "Entities\BankingAccount.php"
    "Payee Entity" = "Entities\Payee.php"
    "Utils Class" = "Utils.php"
    "Inspectable Trait" = "Entities\Inspectable.php"
    "Investment Transactions" = "Entities\Investment\Transaction\*.php"
    "Investment Parsers" = "Parsers\Investment.php"
}

foreach ($feature in $features.Keys) {
    Write-Output "--- $feature ---"
    foreach ($repo in $repos.Keys) {
        $path = $repos[$repo]
        if (Test-Path $path) {
            $pattern = Join-Path $path $features[$feature]
            $files = Get-ChildItem -Path $pattern -File -ErrorAction SilentlyContinue
            if ($files) {
                Write-Output "  ✓ $repo : $($files.Count) file(s)"
            } else {
                Write-Output "  ✗ $repo"
            }
        } else {
            Write-Output "  ? $repo (path not found)"
        }
    }
    Write-Output ""
}

# Count total entities in each repo
Write-Output "=== ENTITY COUNT ==="
foreach ($repo in $repos.Keys) {
    $path = $repos[$repo]
    if (Test-Path $path) {
        $entities = Get-ChildItem -Path (Join-Path $path "Entities") -Recurse -File -ErrorAction SilentlyContinue
        $count = if ($entities) { $entities.Count } else { 0 }
        Write-Output "$repo : $count entity files"
    }
}
