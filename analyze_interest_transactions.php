<?php
/**
 * Mantis #3178: Interest Transaction Analysis Script
 * 
 * This script analyzes bank import transactions to identify interest
 * transactions that are incorrectly marked as Debit when they should be Credit.
 * 
 * USAGE:
 *   php analyze_interest_transactions.php
 * 
 * OUTPUT:
 *   - Summary statistics
 *   - List of suspect transactions
 *   - Recommendations for correction
 * 
 * @author  AI Assistant
 * @date    2025-10-18
 * @mantis  #3178
 */

// Initialize FrontAccounting environment
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/data_checks.inc");

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Analyze interest transactions in the database
 * 
 * @return array Analysis results
 */
function analyzeInterestTransactions()
{
    $results = [];
    
    // Query 1: Count suspect transactions (Interest marked as Debit)
    $sql = "
        SELECT COUNT(*) as total_suspect
        FROM " . TB_PREF . "bi_transactions t
        WHERE t.transactionDC = 'D'
          AND (
            LOWER(t.transactionTitle) LIKE '%interest%'
            OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
            OR LOWER(t.memo) LIKE '%interest%'
          )
    ";
    
    $result = db_query($sql, "Failed to count suspect transactions");
    $row = db_fetch($result);
    $results['suspect_count'] = $row['total_suspect'];
    
    // Query 2: Get detailed list of suspect transactions
    $sql = "
        SELECT 
            t.id,
            t.transDate,
            s.account as bank_account,
            t.transactionDC,
            t.transactionAmount,
            t.transactionTitle,
            t.transactionCodeDesc,
            t.transactionCode,
            t.memo,
            t.status
        FROM " . TB_PREF . "bi_transactions t
        LEFT JOIN " . TB_PREF . "bi_statements s ON t.smt_id = s.id
        WHERE t.transactionDC = 'D'
          AND (
            LOWER(t.transactionTitle) LIKE '%interest%'
            OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
            OR LOWER(t.memo) LIKE '%interest%'
          )
        ORDER BY t.transDate DESC
        LIMIT 100
    ";
    
    $result = db_query($sql, "Failed to fetch suspect transactions");
    $results['suspect_transactions'] = [];
    while ($row = db_fetch($result)) {
        $results['suspect_transactions'][] = $row;
    }
    
    // Query 3: Group by bank account
    $sql = "
        SELECT 
            s.account as bank_account,
            s.currency,
            COUNT(*) as suspect_count,
            SUM(ABS(t.transactionAmount)) as total_amount,
            MIN(t.transDate) as earliest_date,
            MAX(t.transDate) as latest_date
        FROM " . TB_PREF . "bi_transactions t
        LEFT JOIN " . TB_PREF . "bi_statements s ON t.smt_id = s.id
        WHERE t.transactionDC = 'D'
          AND (
            LOWER(t.transactionTitle) LIKE '%interest%'
            OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
            OR LOWER(t.memo) LIKE '%interest%'
          )
        GROUP BY s.account, s.currency
        ORDER BY suspect_count DESC
    ";
    
    $result = db_query($sql, "Failed to group by bank account");
    $results['by_account'] = [];
    while ($row = db_fetch($result)) {
        $results['by_account'][] = $row;
    }
    
    // Query 4: Processing status breakdown
    $sql = "
        SELECT 
            t.status,
            CASE 
                WHEN t.status = 0 THEN 'Pending'
                WHEN t.status = 1 THEN 'Processed'
                ELSE 'Unknown'
            END as status_label,
            COUNT(*) as count,
            SUM(ABS(t.transactionAmount)) as total_amount
        FROM " . TB_PREF . "bi_transactions t
        WHERE t.transactionDC = 'D'
          AND (
            LOWER(t.transactionTitle) LIKE '%interest%'
            OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
            OR LOWER(t.memo) LIKE '%interest%'
          )
        GROUP BY t.status
    ";
    
    $result = db_query($sql, "Failed to get status breakdown");
    $results['by_status'] = [];
    while ($row = db_fetch($result)) {
        $results['by_status'][] = $row;
    }
    
    // Query 5: Compare Debit vs Credit interest transactions
    $sql = "
        SELECT 
            t.transactionDC,
            COUNT(*) as count,
            SUM(ABS(t.transactionAmount)) as total_amount,
            AVG(ABS(t.transactionAmount)) as avg_amount
        FROM " . TB_PREF . "bi_transactions t
        WHERE (
            LOWER(t.transactionTitle) LIKE '%interest%'
            OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
            OR LOWER(t.memo) LIKE '%interest%'
        )
        GROUP BY t.transactionDC
    ";
    
    $result = db_query($sql, "Failed to compare D vs C");
    $results['debit_vs_credit'] = [];
    while ($row = db_fetch($result)) {
        $results['debit_vs_credit'][] = $row;
    }
    
    return $results;
}

/**
 * Display analysis results in readable format
 * 
 * @param array $results Analysis results
 */
function displayResults($results)
{
    echo "================================================================================\n";
    echo "MANTIS #3178: INTEREST TRANSACTION ANALYSIS\n";
    echo "================================================================================\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Summary
    echo "SUMMARY:\n";
    echo "--------\n";
    echo "Total Suspect Transactions: " . $results['suspect_count'] . "\n";
    echo "(Interest transactions marked as Debit)\n\n";
    
    // Debit vs Credit comparison
    if (!empty($results['debit_vs_credit'])) {
        echo "INTEREST TRANSACTIONS BREAKDOWN:\n";
        echo "--------------------------------\n";
        foreach ($results['debit_vs_credit'] as $row) {
            $dc_label = $row['transactionDC'] == 'D' ? 'Debit (SUSPECT)' : 'Credit (Normal)';
            echo sprintf(
                "%s: %d transactions, Total: $%.2f, Average: $%.2f\n",
                $dc_label,
                $row['count'],
                $row['total_amount'],
                $row['avg_amount']
            );
        }
        echo "\n";
    }
    
    // By account
    if (!empty($results['by_account'])) {
        echo "AFFECTED BANK ACCOUNTS:\n";
        echo "-----------------------\n";
        foreach ($results['by_account'] as $row) {
            echo sprintf(
                "Account: %s (%s)\n  Count: %d, Total: $%.2f\n  Date Range: %s to %s\n\n",
                $row['bank_account'],
                $row['currency'],
                $row['suspect_count'],
                $row['total_amount'],
                $row['earliest_date'],
                $row['latest_date']
            );
        }
    }
    
    // By status
    if (!empty($results['by_status'])) {
        echo "PROCESSING STATUS:\n";
        echo "------------------\n";
        foreach ($results['by_status'] as $row) {
            echo sprintf(
                "%s: %d transactions, Total: $%.2f\n",
                $row['status_label'],
                $row['count'],
                $row['total_amount']
            );
        }
        echo "\n";
    }
    
    // Sample transactions
    if (!empty($results['suspect_transactions'])) {
        echo "SAMPLE SUSPECT TRANSACTIONS (First 20):\n";
        echo "---------------------------------------\n";
        $count = 0;
        foreach ($results['suspect_transactions'] as $trx) {
            if ($count >= 20) break;
            
            $status_label = $trx['status'] == 0 ? 'Pending' : 'Processed';
            echo sprintf(
                "ID: %d | Date: %s | Account: %s\n",
                $trx['id'],
                $trx['transDate'],
                $trx['bank_account']
            );
            echo sprintf(
                "  DC: %s | Amount: $%.2f | Status: %s\n",
                $trx['transactionDC'],
                $trx['transactionAmount'],
                $status_label
            );
            echo sprintf(
                "  Title: %s\n",
                $trx['transactionTitle']
            );
            if (!empty($trx['transactionCodeDesc'])) {
                echo sprintf(
                    "  Code Desc: %s\n",
                    $trx['transactionCodeDesc']
                );
            }
            if (!empty($trx['memo'])) {
                echo sprintf(
                    "  Memo: %s\n",
                    $trx['memo']
                );
            }
            echo "\n";
            $count++;
        }
    }
    
    echo "================================================================================\n";
    echo "RECOMMENDATIONS:\n";
    echo "================================================================================\n";
    echo "\n";
    
    if ($results['suspect_count'] > 0) {
        echo "1. REVIEW: Examine the sample transactions above to confirm they are errors\n";
        echo "   - Look for patterns in transaction titles/descriptions\n";
        echo "   - Identify which banks/accounts are affected\n";
        echo "\n";
        
        $pending_count = 0;
        $processed_count = 0;
        foreach ($results['by_status'] as $row) {
            if ($row['status'] == 0) $pending_count = $row['count'];
            if ($row['status'] == 1) $processed_count = $row['count'];
        }
        
        if ($pending_count > 0) {
            echo "2. PENDING TRANSACTIONS ($pending_count):\n";
            echo "   - These have NOT been processed yet\n";
            echo "   - Can be corrected BEFORE processing\n";
            echo "   - Recommended: Add validation/auto-correction in parser\n";
            echo "\n";
        }
        
        if ($processed_count > 0) {
            echo "3. PROCESSED TRANSACTIONS ($processed_count):\n";
            echo "   - These have ALREADY been processed into GL\n";
            echo "   - May require manual GL adjustments\n";
            echo "   - Contact accountant before making corrections\n";
            echo "\n";
        }
        
        echo "4. CODE CHANGES:\n";
        echo "   - Add detection in QFX/MT940 parsers for interest + debit\n";
        echo "   - Flag for manual review or auto-correct to Credit\n";
        echo "   - Add validation warning in process_statements.php\n";
        echo "   - Update TransactionFilterService to identify suspect transactions\n";
        echo "\n";
        
        echo "5. NEXT STEPS:\n";
        echo "   - Run full SQL analysis (sql/mantis_3178_interest_analysis.sql)\n";
        echo "   - Export suspect transactions for accountant review\n";
        echo "   - Create correction script if pattern is confirmed\n";
        echo "   - Update parser logic to prevent future occurrences\n";
        echo "\n";
    } else {
        echo "✓ NO SUSPECT TRANSACTIONS FOUND\n";
        echo "  All interest transactions appear to be correctly marked as Credit.\n";
        echo "\n";
    }
}

// Main execution
try {
    echo "Analyzing interest transactions...\n\n";
    $results = analyzeInterestTransactions();
    displayResults($results);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

?>
