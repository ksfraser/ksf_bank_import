<?php

/**
 * Standalone Test for Manulife CSV Parser
 * 
 * Tests the GenericCsvParser-based Manulife parser without FA dependencies
 * 
 * Usage: php test_manulife_standalone.php qfx_files/20260112_1518404_transactions.csv
 */

if ($argc < 2) {
    echo "Usage: php test_manulife_standalone.php <csv_file>\n";
    exit(1);
}

$csvFile = $argv[1];

if (!file_exists($csvFile)) {
    echo "Error: File not found: $csvFile\n";
    exit(1);
}

echo "Testing Manulife CSV Parser (Standalone)\n";
echo "CSV File: $csvFile\n";
echo "File size: " . filesize($csvFile) . " bytes\n\n";

// Define required classes inline to avoid FA dependencies
if (!class_exists('parser')) {
    abstract class parser {
        abstract function parse($string, $static_data = array(), $debug=false);
    }
}

if (!class_exists('statement')) {
    class statement {
        public $bank;
        public $account;
        public $currency;
        public $timestamp;
        public $startBalance;
        public $endBalance;
        public $number;
        public $sequence;
        public $statementId;
        public $transactions = [];
        
        public function addTransaction($transaction) {
            $this->transactions[] = $transaction;
        }
    }
}

if (!class_exists('transaction')) {
    class transaction {
        public $valueTimestamp;
        public $datePosted;
        public $amount;
        public $memo;
        public $name;
        public $checkNumber;
        public $transactionType;
    }
}

// Load required classes directly
require_once(__DIR__ . '/includes/CsvFieldMapper.php');
require_once(__DIR__ . '/includes/CsvMappingTemplate.php');

// Load GenericCsvParser (will attempt to load parser.php and includes.inc but we already have the classes)
require_once(__DIR__ . '/includes/GenericCsvParser.php');

// Now load Manulife parser
require_once(__DIR__ . '/includes/ro_manulife_csv_parser.php');

// Create parser
$parser = new ro_manulife_csv_parser();

// Read CSV file
$content = file_get_contents($csvFile);

// Show first few lines
echo "CSV Preview (first 5 lines):\n";
echo str_repeat("-", 70) . "\n";
$lines = explode("\n", $content);
foreach (array_slice($lines, 0, 5) as $line) {
    echo substr($line, 0, 70) . "\n";
}
echo str_repeat("-", 70) . "\n\n";

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
        
        // Show first 10 transactions
        echo "First 10 transactions:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-12s | %-10s | %-10s | %-40s\n", "Date", "Type", "Amount", "Payee");
        echo str_repeat("-", 80) . "\n";
        
        $count = 0;
        foreach ($statements as $stmt) {
            foreach ($stmt->transactions as $trx) {
                if ($count >= 10) break 2;
                
                printf("%-12s | %-10s | %10s | %-40s\n",
                    $trx->valueTimestamp,
                    $trx->transactionType ?? 'N/A',
                    '$' . number_format($trx->amount, 2),
                    substr($trx->name, 0, 40)
                );
                
                $count++;
            }
        }
        
        echo str_repeat("-", 80) . "\n";
        
        // Show memo samples
        echo "\nMemo/Description samples:\n";
        echo str_repeat("-", 80) . "\n";
        $count = 0;
        foreach ($statements as $stmt) {
            foreach ($stmt->transactions as $trx) {
                if ($count >= 5) break 2;
                
                echo "Name: " . $trx->name . "\n";
                echo "Memo: " . $trx->memo . "\n";
                echo "\n";
                
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
