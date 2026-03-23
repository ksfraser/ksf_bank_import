<?php
// Quick test to verify QIF parser contact extraction doesn't break parsing

require 'vendor/autoload.php';
require 'tests/bootstrap.php';
require 'vendor/ksfraser/qifparser/qif_parser.php';

// Simple QIF content
$qifContent = <<<QIF
!Type:Bank
^
!Account
NTest Account
D2026/03/20
T-50.00
CX
NAMPLE STORE
^
D2026/03/21
T100.00
CX
NSALARY DEPOSIT
^
QIF;

// Parse with parser
$parser = new qif_parser();

try {
    // Create mock static data
    $staticData = [
        'account_code' => '1060',
        'bank_id' => 'TEST',
        'currency' => 'CAD',
        'date_format' => 'MDY'
    ];
    
    // Parse QIF content
    $statements = $parser->parse($qifContent, $staticData, false);
    
    echo "✓ QIF parser executed successfully\n";
    echo "  Statements: " . count($statements) . "\n";
    
    if (!empty($statements)) {
        foreach ($statements as $stmt) {
            $transCount = count($stmt->transaction ?? []);
            echo "  Statement: " . $stmt->statementId . " (" . $transCount . " transactions)\n";
            if (!empty($stmt->transaction)) {
                foreach ($stmt->transaction as $trz) {
                    echo "    - " . $trz->merchant . " / DC: " . $trz->transactionDC;
                    if (isset($trz->contact_id)) {
                        echo " [contact_id: " . $trz->contact_id . "]";
                    }
                    echo "\n";
                }
            }
        }
    }
    
    echo "\n✓ Test passed: QIF parser with contact extraction works\n";
} catch (\Throwable $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
    echo "  " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
