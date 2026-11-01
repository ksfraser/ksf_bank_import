<?php

/**
 * Test Manulife CSV Parser
 * 
 * Tests the new GenericCsvParser-based Manulife parser
 * 
 * Usage: php test_manulife_parser.php qfx_files/20260112_1518404_transactions.csv
 */

if ($argc < 2) {
    echo "Usage: php test_manulife_parser.php <csv_file>\n";
    exit(1);
}

$csvFile = $argv[1];

if (!file_exists($csvFile)) {
    echo "Error: File not found: $csvFile\n";
    exit(1);
}

echo "Testing Manulife CSV Parser\n";
echo "CSV File: $csvFile\n";
echo "File size: " . filesize($csvFile) . " bytes\n\n";

// Load required files
require_once(__DIR__ . '/includes.inc');
require_once(__DIR__ . '/includes/GenericCsvParser.php');
require_once(__DIR__ . '/includes/ro_manulife_csv_parser.php');

// Create parser
$parser = new ro_manulife_csv_parser();

// Read CSV file
$content = file_get_contents($csvFile);

// Static data (as would be provided by import form)
$static_data = [
    'bank_name' => 'Manulife Bank',
    'account' => '1518404',
    'currency' => 'CAD'
];

echo "Parsing CSV...\n\n";

try {
    // Parse with debug enabled
    $statements = $parser->parse($content, $static_data, true);
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "PARSING COMPLETE\n";
    echo str_repeat("=", 70) . "\n\n";
    
    if (empty($statements)) {
        echo "No statements generated. Mapping review may be required.\n";
    } else {
        echo "Statements generated: " . count($statements) . "\n\n";
        
        // Display statement summary
        foreach ($statements as $stmtId => $stmt) {
            echo "Statement ID: $stmtId\n";
            echo "Bank: {$stmt->bank}\n";
            echo "Account: {$stmt->account}\n";
            echo "Currency: {$stmt->currency}\n";
            echo "Date: {$stmt->timestamp}\n";
            echo "Transactions: " . count($stmt->transactions) . "\n";
            
            // Calculate totals
            $totalDebits = 0;
            $totalCredits = 0;
            foreach ($stmt->transactions as $trx) {
                if ($trx->amount < 0) {
                    $totalDebits += abs($trx->amount);
                } else {
                    $totalCredits += $trx->amount;
                }
            }
            
            echo "Total Debits: $" . number_format($totalDebits, 2) . "\n";
            echo "Total Credits: $" . number_format($totalCredits, 2) . "\n";
            echo "Net: $" . number_format($totalCredits - $totalDebits, 2) . "\n";
            echo "\n";
        }
        
        // Show first 5 transactions
        echo "First 5 transactions:\n";
        echo str_repeat("-", 70) . "\n";
        
        $count = 0;
        foreach ($statements as $stmt) {
            foreach ($stmt->transactions as $trx) {
                if ($count >= 5) break 2;
                
                printf("%-12s | %8s | %-45s\n",
                    $trx->valueTimestamp,
                    '$' . number_format($trx->amount, 2),
                    substr($trx->name, 0, 45)
                );
                
                $count++;
            }
        }
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\nTest completed successfully!\n";
