<?php
/**
 * Manual Test Script for Paired Transfer Processing
 * 
 * This script tests the paired transfer matching logic using real QFX files
 * without requiring a full database setup.
 * 
 * Usage: php manual_test_paired_transfers.php
 * 
 * @package    KsfBankImport
 * @subpackage Tests
 * @author     Kevin Fraser
 * @since      1.0.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n========================================\n";
echo "Paired Transfer Matching Test\n";
echo "========================================\n\n";

// Load required classes
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Services/TransferDirectionAnalyzer.php';

use OfxParser\Parser;
use KsfBankImport\Services\TransferDirectionAnalyzer;

/**
 * Parse a QFX file and extract transactions
 * 
 * @param string $filename Path to QFX file
 * @return array Array of transactions with normalized data
 */
function parseQfxFile($filename) {
    echo "Parsing: $filename\n";
    
    if (!file_exists($filename)) {
        echo "  ERROR: File not found!\n";
        return [];
    }
    
    try {
        $ofxParser = new Parser();
        $ofx = $ofxParser->loadFromFile($filename);
        
        $bankAccounts = $ofx->bankAccounts;
        
        if (empty($bankAccounts)) {
            echo "  No bank accounts found in file\n";
            return [];
        }
        
        $transactions = [];
        foreach ($bankAccounts as $account) {
            $accountId = $account->accountNumber;
            $statement = $account->statement;
            
            echo "  Account: $accountId\n";
            echo "  Transactions: " . count($statement->transactions) . "\n";
            
            foreach ($statement->transactions as $index => $transaction) {
                $transactions[] = [
                    'id' => $index + 1,
                    'accountId' => $accountId,
                    'transactionTitle' => $transaction->name,
                    'transactionMemo' => $transaction->memo,
                    'transactionDC' => $transaction->amount > 0 ? 'C' : 'D',
                    'transactionAmount' => $transaction->amount,
                    'valueTimestamp' => $transaction->date->format('Y-m-d'),
                    'uniqueId' => $transaction->uniqueId,
                    'type' => $transaction->type
                ];
            }
        }
        
        echo "  Total transactions extracted: " . count($transactions) . "\n\n";
        return $transactions;
        
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n\n";
        return [];
    }
}

/**
 * Find potential paired transfers between two accounts
 * 
 * @param array $account1Transactions Transactions from first account
 * @param array $account2Transactions Transactions from second account
 * @param int $dayWindow Number of days to look for matches (default ±2)
 * @return array Array of matched pairs
 */
function findPairedTransfers($account1Transactions, $account2Transactions, $dayWindow = 2) {
    $matches = [];
    
    foreach ($account1Transactions as $trz1) {
        foreach ($account2Transactions as $trz2) {
            // Check if amounts match (one should be positive, one negative)
            $amount1 = abs($trz1['transactionAmount']);
            $amount2 = abs($trz2['transactionAmount']);
            
            if (abs($amount1 - $amount2) > 0.01) {
                continue; // Amounts don't match
            }
            
            // Check if DC indicators are opposite
            if ($trz1['transactionDC'] === $trz2['transactionDC']) {
                continue; // Both debit or both credit - not a transfer
            }
            
            // Check if dates are within window
            $date1 = new DateTime($trz1['valueTimestamp']);
            $date2 = new DateTime($trz2['valueTimestamp']);
            $daysDiff = abs($date1->diff($date2)->days);
            
            if ($daysDiff > $dayWindow) {
                continue; // Dates too far apart
            }
            
            // We have a match!
            $matches[] = [
                'transaction1' => $trz1,
                'transaction2' => $trz2,
                'amount' => $amount1,
                'daysDifference' => $daysDiff
            ];
        }
    }
    
    return $matches;
}

/**
 * Test the TransferDirectionAnalyzer with matched pairs
 * 
 * @param array $matches Array of matched transaction pairs
 * @return void
 */
function testTransferDirectionAnalyzer($matches) {
    if (empty($matches)) {
        echo "No matches to test with TransferDirectionAnalyzer\n\n";
        return;
    }
    
    echo "Testing TransferDirectionAnalyzer\n";
    echo "==================================\n\n";
    
    $analyzer = new TransferDirectionAnalyzer();
    
    foreach ($matches as $index => $match) {
        $trz1 = $match['transaction1'];
        $trz2 = $match['transaction2'];
        
        $account1 = [
            'id' => $trz1['accountId'],
            'name' => "Account " . $trz1['accountId']
        ];
        
        $account2 = [
            'id' => $trz2['accountId'],
            'name' => "Account " . $trz2['accountId']
        ];
        
        echo "Match #" . ($index + 1) . ":\n";
        echo "  Transaction 1: {$trz1['transactionTitle']} ({$trz1['transactionDC']}) - \${$trz1['transactionAmount']}\n";
        echo "  Transaction 2: {$trz2['transactionTitle']} ({$trz2['transactionDC']}) - \${$trz2['transactionAmount']}\n";
        echo "  Days apart: {$match['daysDifference']}\n";
        
        try {
            $result = $analyzer->analyze($trz1, $trz2, $account1, $account2);
            
            echo "  Analysis Result:\n";
            echo "    FROM Account: {$result['from_account']}\n";
            echo "    TO Account: {$result['to_account']}\n";
            echo "    Amount: \${$result['amount']}\n";
            echo "    Date: {$result['date']}\n";
            echo "    Memo: {$result['memo']}\n";
            echo "  ✓ Analysis successful\n\n";
            
        } catch (Exception $e) {
            echo "  ✗ Analysis failed: " . $e->getMessage() . "\n\n";
        }
    }
}

// ============================================================================
// MAIN TEST EXECUTION
// ============================================================================

echo "Test 1: Parse Manulife QFX\n";
echo "--------------------------\n";
$manuTransactions = parseQfxFile(__DIR__ . '/includes/MANU.qfx');

echo "Test 2: Parse CIBC HISA QFX\n";
echo "----------------------------\n";
$cibcTransactions = parseQfxFile(__DIR__ . '/includes/CIBC_SAVINGS.qfx');

if (empty($manuTransactions) || empty($cibcTransactions)) {
    echo "ERROR: Unable to load transactions from one or both files\n";
    echo "Make sure MANU.qfx and CIBC_SAVINGS.qfx exist in the includes/ directory\n";
    exit(1);
}

echo "Test 3: Find Paired Transfers\n";
echo "------------------------------\n";
$matches = findPairedTransfers($manuTransactions, $cibcTransactions);

echo "Found " . count($matches) . " potential paired transfers\n\n";

if (count($matches) > 0) {
    echo "Matched Pairs:\n";
    foreach ($matches as $index => $match) {
        echo "\nPair #" . ($index + 1) . ":\n";
        echo "  Amount: \$" . number_format($match['amount'], 2) . "\n";
        echo "  Days apart: {$match['daysDifference']}\n";
        echo "  Transaction 1:\n";
        echo "    Account: {$match['transaction1']['accountId']}\n";
        echo "    Title: {$match['transaction1']['transactionTitle']}\n";
        echo "    DC: {$match['transaction1']['transactionDC']}\n";
        echo "    Date: {$match['transaction1']['valueTimestamp']}\n";
        echo "  Transaction 2:\n";
        echo "    Account: {$match['transaction2']['accountId']}\n";
        echo "    Title: {$match['transaction2']['transactionTitle']}\n";
        echo "    DC: {$match['transaction2']['transactionDC']}\n";
        echo "    Date: {$match['transaction2']['valueTimestamp']}\n";
    }
    echo "\n";
    
    echo "Test 4: Analyze Transfer Direction\n";
    echo "-----------------------------------\n";
    testTransferDirectionAnalyzer($matches);
} else {
    echo "No paired transfers found between these accounts.\n";
    echo "This may be expected if the test files don't contain matching transfers.\n\n";
}

echo "========================================\n";
echo "Test Complete\n";
echo "========================================\n\n";

// Show summary statistics
echo "Summary:\n";
echo "--------\n";
echo "Manulife transactions: " . count($manuTransactions) . "\n";
echo "CIBC HISA transactions: " . count($cibcTransactions) . "\n";
echo "Matched pairs: " . count($matches) . "\n";

// Show sample transactions from each account
if (count($manuTransactions) > 0) {
    echo "\nSample Manulife transaction:\n";
    $sample = $manuTransactions[0];
    echo "  Title: {$sample['transactionTitle']}\n";
    echo "  Amount: \${$sample['transactionAmount']}\n";
    echo "  Date: {$sample['valueTimestamp']}\n";
    echo "  DC: {$sample['transactionDC']}\n";
}

if (count($cibcTransactions) > 0) {
    echo "\nSample CIBC transaction:\n";
    $sample = $cibcTransactions[0];
    echo "  Title: {$sample['transactionTitle']}\n";
    echo "  Amount: \${$sample['transactionAmount']}\n";
    echo "  Date: {$sample['valueTimestamp']}\n";
    echo "  DC: {$sample['transactionDC']}\n";
}

echo "\n";
