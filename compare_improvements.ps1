# Compare specific entities to find bug fixes
Write-Output "=== Comparing Transaction.php for bug fixes ==="
Write-Output ""

# Check if jacques has the MEMO fix
$jacquesTransaction = Get-Content "lib\jacques-ofxparser\lib\OfxParser\Entities\Transaction.php" -Raw
$ksfTransaction = Get-Content "lib\ksf_ofxparser\src\Ksfraser\Transaction.php" -Raw

if ($jacquesTransaction -match "MEMO.*empty") {
    Write-Output "✅ jacques has MEMO empty tag fix"
} else {
    Write-Output "❌ jacques does NOT have MEMO empty tag fix"
}

if ($ksfTransaction -match "MEMO.*empty") {
    Write-Output "✅ ksf has MEMO empty tag fix"
} else {
    Write-Output "❌ ksf does NOT have MEMO empty tag fix"
}

Write-Output ""
Write-Output "=== Comparing Statement.php ==="
$jacquesStatement = Get-Content "lib\jacques-ofxparser\lib\OfxParser\Entities\Statement.php" -Raw
$ksfStatement = Get-Content "lib\ksf_ofxparser\src\Ksfraser\Statement.php" -Raw

# Check method signatures
if ($jacquesStatement -match "declare\(strict_types=1\)") {
    Write-Output "✅ jacques has strict_types"
} else {
    Write-Output "❌ jacques missing strict_types"
}

if ($ksfStatement -match "declare\(strict_types=1\)") {
    Write-Output "✅ ksf has strict_types"
} else {
    Write-Output "❌ ksf missing strict_types"
}
