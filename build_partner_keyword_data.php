<?php
/**
 * Build Partner Keyword Data from Existing Transactions
 *
 * This script processes all bi_transactions records that have been settled
 * (have fa_trans_type and partner info) and extracts keywords from transaction
 * details to populate bi_partners_data with occurrence counts.
 *
 * Purpose: Enable intelligent keyword-based pattern matching
 * Example: "Internet Transfer" → Bank Transfer (high score)
 *          "Internet Domain" → QE-Business Expense (high score)
 *
 * @package    KSF Bank Import
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

$path_to_root = "../..";
$page_security = 'SA_BANKTRANSVIEW';

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/modules/ksf_bank_import/includes/pdata.inc");

page(_($help_context = "Build Partner Keyword Data"));

/**
 * Extract keywords from transaction text
 *
 * Tokenizes text and filters out noise words
 * Uses configuration for minimum keyword length
 *
 * @param string $text Transaction memo/title/account text
 * @return array Array of keywords (lowercase, unique, configurable min length)
 */
function extract_keywords($text) {
    if (empty($text)) {
        return array();
    }
    
    // Get minimum keyword length from config (default 3)
    $min_keyword_length = 3;
    if (class_exists('\Ksfraser\FaBankImport\Config\ConfigService')) {
        try {
            $configService = \Ksfraser\FaBankImport\Config\ConfigService::getInstance();
            $min_keyword_length = (int)$configService->get('pattern_matching.min_keyword_length', 3);
        } catch (\Exception $e) {
            // Config not available, use default
        }
    }
    
    // Normalize: lowercase, remove special chars, split on whitespace
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    // Filter: remove common noise words and short words
    $stopwords = array(
        'the', 'and', 'or', 'for', 'to', 'from', 'in', 'on', 'at', 'by',
        'with', 'of', 'as', 'is', 'was', 'be', 'are', 'were', 'been',
        'has', 'have', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
        'this', 'that', 'these', 'those', 'it', 'its', 'an', 'a',
        'payment', 'transaction', 'transfer', 'deposit', 'withdrawal'
    );
    
    $keywords = array();
    foreach ($words as $word) {
        // Keep words that meet min length and not stopwords
        if (strlen($word) >= $min_keyword_length && !in_array($word, $stopwords)) {
            $keywords[$word] = true; // Use array key for uniqueness
        }
    }
    
    return array_keys($keywords);
}

/**
 * Get partner type from transaction
 *
 * @param array $row Transaction record
 * @return string Partner type code or null
 */
function get_partner_type_from_transaction($row) {
    // Map FA transaction types to partner types
    $type_map = array(
        ST_SUPPAYMENT => PT_SUPPLIER,      // 22
        ST_SUPPCREDIT => PT_SUPPLIER,      // 21
        ST_BANKDEPOSIT => PT_SUPPLIER,     // 2 (when supplier refund)
        ST_CUSTPAYMENT => PT_CUSTOMER,     // 12
        ST_CUSTCREDIT => PT_CUSTOMER,      // 11
        ST_BANKPAYMENT => ST_BANKPAYMENT,  // 1 (Quick Entry)
        ST_BANKTRANSFER => ST_BANKTRANSFER // 4
    );
    
    $fa_trans_type = $row['fa_trans_type'];
    
    if (isset($type_map[$fa_trans_type])) {
        return $type_map[$fa_trans_type];
    }
    
    return null;
}

/**
 * Get partner ID from transaction's FA entry
 *
 * Looks up the actual FA transaction to get partner ID
 *
 * @param int $trans_no Transaction number
 * @param int $trans_type Transaction type
 * @return array [partner_id, partner_detail_id, partner_type]
 */
function get_partner_from_fa_trans($trans_no, $trans_type) {
    $counterparty = get_trans_counterparty($trans_no, $trans_type);
    
    if (empty($counterparty)) {
        return array('partner_id' => 0, 'partner_detail_id' => 0, 'partner_type' => 0);
    }
    
    $partner_id = 0;
    $partner_detail_id = 0;
    $partner_type = 0;
    
    if (isset($counterparty['supplier_id']) && $counterparty['supplier_id']) {
        $partner_id = $counterparty['supplier_id'];
        $partner_detail_id = -1; // Suppliers don't have branches
        $partner_type = PT_SUPPLIER;
    } elseif (isset($counterparty['debtor_no']) && $counterparty['debtor_no']) {
        $partner_id = $counterparty['debtor_no'];
        $partner_detail_id = $counterparty['branch_code'] ?? 0;
        $partner_type = PT_CUSTOMER;
    } elseif (isset($counterparty['account_code']) && $counterparty['account_code']) {
        // Quick Entry - use account code as identifier
        $partner_id = $counterparty['account_code'];
        $partner_detail_id = 0;
        $partner_type = ST_BANKPAYMENT;
    } elseif ($trans_type == ST_BANKTRANSFER) {
        // Bank Transfer - use counterparty info (from/to bank accounts in GL trans)
        if (!empty($counterparty) && isset($counterparty[0])) {
            // Get bank account from GL transaction
            $partner_id = $counterparty[0]['account'] ?? 0;
            $partner_detail_id = 0;
            $partner_type = ST_BANKTRANSFER;
        }
    }
    
    return array(
        'partner_id' => $partner_id,
        'partner_detail_id' => $partner_detail_id,
        'partner_type' => $partner_type
    );
}

/**
 * Increment keyword occurrence for a partner
 *
 * Inserts or updates bi_partners_data with occurrence count
 *
 * @param int $partner_id Partner ID
 * @param int $partner_detail_id Partner detail ID (branch for customers)
 * @param int $partner_type Partner type constant
 * @param string $keyword Single keyword
 * @return bool Success
 */
function increment_keyword_occurrence($partner_id, $partner_detail_id, $partner_type, $keyword) {
    if (empty($keyword) || !$partner_id || !$partner_type) {
        return false;
    }
    
    $sql = "INSERT INTO " . TB_PREF . "bi_partners_data 
            (partner_id, partner_detail_id, partner_type, data, occurrence_count)
            VALUES (
                " . db_escape($partner_id) . ",
                " . db_escape($partner_detail_id) . ",
                " . db_escape($partner_type) . ",
                " . db_escape($keyword) . ",
                1
            )
            ON DUPLICATE KEY UPDATE
                occurrence_count = occurrence_count + 1";
    
    return db_query($sql, "Could not update keyword occurrence");
}

/**
 * Process all settled transactions and build keyword data
 *
 * @param bool $dry_run If true, only show what would be done
 * @return array Statistics
 */
function process_all_transactions($dry_run = false) {
    $stats = array(
        'transactions_processed' => 0,
        'keywords_added' => 0,
        'keywords_updated' => 0,
        'errors' => 0
    );
    
    // Get all transactions that have been settled (have FA trans info)
    $sql = "SELECT * FROM " . TB_PREF . "bi_transactions 
            WHERE fa_trans_no > 0 
            AND fa_trans_type > 0
            AND status = 1
            ORDER BY id";
    
    $result = db_query($sql, "Could not get transactions");
    
    while ($row = db_fetch($result)) {
        $stats['transactions_processed']++;
        
        // Get partner info from FA transaction
        $partner_info = get_partner_from_fa_trans($row['fa_trans_no'], $row['fa_trans_type']);
        
        if (!$partner_info['partner_id'] || !$partner_info['partner_type']) {
            display_notification("Skipping transaction {$row['id']}: No partner info found");
            continue;
        }
        
        // Collect text from multiple fields
        $text_fields = array(
            $row['account'],
            $row['accountName'],
            $row['transactionTitle'],
            $row['memo'],
            $row['merchant'],
            $row['category']
        );
        
        $all_text = implode(' ', array_filter($text_fields));
        
        // Extract keywords
        $keywords = extract_keywords($all_text);
        
        if (empty($keywords)) {
            continue;
        }
        
        display_notification(
            "Transaction {$row['id']} (FA {$row['fa_trans_type']}-{$row['fa_trans_no']}): " .
            "Partner {$partner_info['partner_id']} Type {$partner_info['partner_type']}: " .
            count($keywords) . " keywords: " . implode(', ', $keywords)
        );
        
        // Add/update each keyword
        if (!$dry_run) {
            foreach ($keywords as $keyword) {
                // Check if keyword already exists for this partner
                $existing = get_partner_data(
                    $partner_info['partner_id'],
                    $partner_info['partner_type'],
                    $partner_info['partner_detail_id']
                );
                
                $is_update = !empty($existing) && strpos($existing['data'], $keyword) !== false;
                
                if (increment_keyword_occurrence(
                    $partner_info['partner_id'],
                    $partner_info['partner_detail_id'],
                    $partner_info['partner_type'],
                    $keyword
                )) {
                    if ($is_update) {
                        $stats['keywords_updated']++;
                    } else {
                        $stats['keywords_added']++;
                    }
                } else {
                    $stats['errors']++;
                }
            }
        } else {
            $stats['keywords_added'] += count($keywords);
        }
    }
    
    return $stats;
}

// ============================================================================
// Main Execution
// ============================================================================

start_form();

// Check if migration has been run
$test_sql = "SHOW COLUMNS FROM " . TB_PREF . "bi_partners_data LIKE 'occurrence_count'";
$test_result = db_query($test_sql, "Could not check table structure");
$has_occurrence_count = db_num_rows($test_result) > 0;

if (!$has_occurrence_count) {
    display_error(
        "ERROR: The bi_partners_data table needs to be migrated first.<br>" .
        "Please run: <code>sql/add_occurrence_count_to_bi_partners_data.sql</code>"
    );
    end_form();
    end_page();
    exit;
}

echo "<h2>Build Partner Keyword Data</h2>";
echo "<p>This script will process all settled transactions and extract keywords " .
     "to populate bi_partners_data with occurrence counts for intelligent pattern matching.</p>";

// Check for action
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'dry_run') {
    echo "<h3>Dry Run - Preview Mode</h3>";
    
    begin_transaction();
    $stats = process_all_transactions(true);
    rollback_transaction();
    
    echo "<div class='success'>";
    echo "<h4>Dry Run Complete:</h4>";
    echo "<ul>";
    echo "<li>Transactions to process: {$stats['transactions_processed']}</li>";
    echo "<li>Keywords to add: {$stats['keywords_added']}</li>";
    echo "<li>Estimated errors: {$stats['errors']}</li>";
    echo "</ul>";
    echo "<p><strong>No changes were made to the database.</strong></p>";
    echo "</div>";
    
} elseif ($action == 'process') {
    echo "<h3>Processing Transactions...</h3>";
    
    begin_transaction();
    
    try {
        $stats = process_all_transactions(false);
        commit_transaction();
        
        echo "<div class='success'>";
        echo "<h4>Processing Complete:</h4>";
        echo "<ul>";
        echo "<li>Transactions processed: {$stats['transactions_processed']}</li>";
        echo "<li>Keywords added: {$stats['keywords_added']}</li>";
        echo "<li>Keywords updated: {$stats['keywords_updated']}</li>";
        echo "<li>Errors: {$stats['errors']}</li>";
        echo "</ul>";
        echo "</div>";
        
    } catch (Exception $e) {
        rollback_transaction();
        display_error("Error during processing: " . $e->getMessage());
    }
    
} else {
    // Show form
    echo "<h3>Options</h3>";
    
    // Show current stats
    $trans_count_sql = "SELECT COUNT(*) as cnt FROM " . TB_PREF . "bi_transactions 
                        WHERE fa_trans_no > 0 AND fa_trans_type > 0 AND status = 1";
    $trans_result = db_query($trans_count_sql, "Could not count transactions");
    $trans_count = db_fetch($trans_result)['cnt'];
    
    $partner_data_sql = "SELECT COUNT(*) as cnt, SUM(occurrence_count) as total_occurrences 
                         FROM " . TB_PREF . "bi_partners_data";
    $partner_result = db_query($partner_data_sql, "Could not count partner data");
    $partner_stats = db_fetch($partner_result);
    
    echo "<p><strong>Current Database State:</strong></p>";
    echo "<ul>";
    echo "<li>Settled transactions: {$trans_count}</li>";
    echo "<li>Partner keyword records: {$partner_stats['cnt']}</li>";
    echo "<li>Total keyword occurrences: {$partner_stats['total_occurrences']}</li>";
    echo "</ul>";
    
    submit('action', 'Preview (Dry Run)', true, 'Dry Run', false);
    echo "&nbsp;&nbsp;";
    submit('action', 'Process All Transactions', true, 'Process', false);
}

end_form();
end_page();
