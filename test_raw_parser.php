#!/usr/bin/php -f
<?php
/**
 * Direct OFX Parser Test - bypasses qfx_parser.php wrapper
 * Tests the raw OfxParser\Parser class directly
 * 
 * Usage: php test_raw_parser.php [qfx_file]
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Direct OFX Parser Test ===\n\n";

// Test file from command line or use default
$test_file = $argv[1] ?? __DIR__ . '/includes/test.qfx';

if (!file_exists($test_file)) {
    die("ERROR: Test file not found: $test_file\n");
}

echo "Test File: $test_file\n";
echo "File Size: " . number_format(filesize($test_file)) . " bytes\n\n";

// Test with ksf_fork
echo "=== Testing ksf_fork ===\n";

// Manual load since PSR-0 mapping is incorrect
// The files are in src/Ksfraser/ but declare namespace OfxParser
spl_autoload_register(function($class) {
    $prefix = 'OfxParser\\';
    $base_dir = __DIR__ . '/lib/ksf_ofxparser/src/Ksfraser/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

try {
    $content = file_get_contents($test_file);
    
    echo "Loading file into parser...\n";
    $ofxParser = new \OfxParser\Parser();
    $ofx = $ofxParser->loadFromString($content);
    
    echo "SUCCESS: File parsed!\n\n";
    echo "Bank Account:\n";
    $account = $ofx->bankAccounts[0] ?? null;
    if ($account) {
        echo "  Account Number: " . $account->accountNumber . "\n";
        echo "  Routing Number: " . $account->routingNumber . "\n";
        echo "  Account Type: " . $account->accountType . "\n";
        echo "  Balance: " . $account->balance . "\n";
        echo "  Currency: " . ($account->currency ?? 'N/A') . "\n";
    }
    
    echo "\nTransactions:\n";
    $statement = $account->statement ?? null;
    if ($statement && $statement->transactions) {
        echo "  Total Transactions: " . count($statement->transactions) . "\n";
        
        if (count($statement->transactions) > 0) {
            $first = $statement->transactions[0];
            echo "\n  First Transaction:\n";
            echo "    Type: " . $first->type . "\n";
            echo "    Date: " . $first->date->format('Y-m-d') . "\n";
            echo "    Amount: " . $first->amount . "\n";
            echo "    Name: " . $first->name . "\n";
            echo "    Memo: " . ($first->memo ?? 'N/A') . "\n";
        }
        
        // Check for problematic transactions
        echo "\n  Checking for transactions with newlines in memo...\n";
        foreach ($statement->transactions as $i => $trans) {
            if (isset($trans->memo) && (strpos($trans->memo, "\n") !== false || strpos($trans->memo, "\r") !== false)) {
                echo "  WARNING: Transaction #$i has newlines in memo:\n";
                echo "    Memo: " . json_encode($trans->memo) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
