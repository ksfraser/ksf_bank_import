#!/usr/bin/php -f
<?php
/**
 * Test the parser with real files
 * 
 * Usage: php test_production_parser.php [qfx_file]
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== OFX Parser Test ===\n\n";

// Test file from command line or use default
$test_file = $argv[1] ?? __DIR__ . '/includes/CIBC_VISA.qfx';

if (!file_exists($test_file)) {
    die("ERROR: Test file not found: $test_file\n");
}

echo "Test File: $test_file\n";
echo "File Size: " . number_format(filesize($test_file)) . " bytes\n\n";

// Load the parser via composer autoload
require_once __DIR__ . '/vendor/autoload.php';

try {
    $content = file_get_contents($test_file);
    
    echo "Content preview (first 500 chars):\n";
    echo substr($content, 0, 500) . "\n...\n\n";
    
    echo "Loading file into parser...\n";
    $ofxParser = new \OfxParser\Parser();
    $ofx = $ofxParser->loadFromString($content);
    
    echo "SUCCESS: File parsed!\n\n";
    
    // Bank Account
    echo "Bank Accounts: " . count($ofx->bankAccounts) . "\n";
    if (count($ofx->bankAccounts) > 0) {
        $account = $ofx->bankAccounts[0];
        echo "  Account Number: " . $account->accountNumber . "\n";
        echo "  Account Type: " . $account->accountType . "\n";
        echo "  Balance: " . $account->balance . "\n";
        
        $statement = $account->statement ?? null;
        if ($statement && $statement->transactions) {
            echo "  Transactions: " . count($statement->transactions) . "\n\n";
            
            // Show first few transactions
            echo "First 3 transactions:\n";
            foreach (array_slice($statement->transactions, 0, 3) as $i => $trans) {
                echo "  " . ($i + 1) . ". " . $trans->date->format('Y-m-d') . " | ";
                echo $trans->type . " | ";
                echo "\$" . number_format($trans->amount, 2) . " | ";
                echo $trans->name . "\n";
                if (!empty($trans->memo)) {
                    echo "     Memo: " . substr($trans->memo, 0, 60);
                    if (strlen($trans->memo) > 60) echo "...";
                    echo "\n";
                    
                    // Check for newlines
                    if (strpos($trans->memo, "\n") !== false || strpos($trans->memo, "\r") !== false) {
                        echo "     ⚠️  Contains newlines: " . json_encode($trans->memo) . "\n";
                    }
                }
            }
            
            // Check all transactions for issues
            echo "\nScanning all transactions for issues...\n";
            $issues = 0;
            foreach ($statement->transactions as $i => $trans) {
                if (isset($trans->memo) && (strpos($trans->memo, "\n") !== false || strpos($trans->memo, "\r") !== false)) {
                    $issues++;
                    if ($issues <= 5) {
                        echo "  Transaction #$i has newlines in memo\n";
                    }
                }
            }
            if ($issues > 5) {
                echo "  ... and " . ($issues - 5) . " more transactions with newlines\n";
            }
            if ($issues == 0) {
                echo "  ✓ No newline issues found\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Show more context for XML errors
    if (strpos($e->getMessage(), 'LibXMLError') !== false || 
        strpos($e->getMessage(), 'xml') !== false) {
        echo "\n📋 This appears to be an XML parsing error.\n";
        echo "CIBC files often have newlines within tags which breaks XML parsing.\n\n";
        
        // Try to show the problematic content
        echo "Checking file content around line 1...\n";
        $lines = explode("\n", $content);
        foreach (array_slice($lines, 0, 50) as $i => $line) {
            if (strpos($line, '<MEMO>') !== false || strpos($line, '<NAME>') !== false) {
                echo "Line " . ($i + 1) . ": " . substr($line, 0, 100) . "\n";
            }
        }
    }
    
    echo "\nFull error:\n";
    print_r($e);
    
} catch (\Error $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
