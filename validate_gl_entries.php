<?php
/**
 * Mantis #2713: Import Bank Transaction Files - Validate Entries
 * 
 * This screen validates that imported bank transactions match their associated GL entries
 * - Checks that trans_type and trans_no exist
 * - Verifies amounts match
 * - Flags mismatches for review
 * - Suggests possible matches when validation fails
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20251018
 */

$page_security = 'SA_BANKTRANSVIEW';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_trans.inc");

// Include the validator class
require_once(__DIR__ . '/src/Ksfraser/FaBankImport/services/TransactionGLValidator.php');

use Ksfraser\FaBankImport\Services\TransactionGLValidator;

page(_($help_context = "Validate Bank Import GL Entries"), false, false, "", $js="");

// Display module menu
include_once "views/module_menu_view.php";
$menu = new \Views\ModuleMenuView();
$menu->renderMenu();

//-----------------------------------------------------------------------------------

/**
 * Display validation results in a table
 */
function display_validation_results($results)
{
    if (empty($results['results'])) {
        display_notification("All transactions validated successfully! No issues found.");
        return;
    }
    
    // Display summary
    start_table(TABLESTYLE);
    $th = array(
        _("Summary"),
        _("Count")
    );
    table_header($th);
    
    label_row("Total Transactions Checked:", $results['total_checked']);
    label_row("Issues Found:", $results['issues_found']);
    label_row("Missing GL Entries:", $results['summary']['missing_gl']);
    label_row("Amount Mismatches:", $results['summary']['amount_mismatch']);
    label_row("Date Warnings:", $results['summary']['date_warnings']);
    label_row("Account Warnings:", $results['summary']['account_warnings']);
    
    if ($results['summary']['total_variance'] > 0) {
        label_row("Total Amount Variance:", 
                  number_format2($results['summary']['total_variance'], 2));
    }
    
    end_table(1);
    
    // Display detailed results
    div_start('validation_details');
    echo "<h3>Validation Details</h3>";
    
    start_table(TABLESTYLE2);
    $th = array(
        _("Trans ID"),
        _("FA Type:No"),
        _("Bank Amount"),
        _("GL Amount"),
        _("Variance"),
        _("Status"),
        _("Details"),
        _("Actions")
    );
    table_header($th);
    
    foreach ($results['results'] as $result) {
        start_row();
        
        // Transaction ID
        label_cell($result['trans_id']);
        
        // FA Type:No with link
        $link = "<a href='../../gl/view/gl_trans_view.php?type_id={$result['fa_trans_type']}&trans_no={$result['fa_trans_no']}' target='_blank'>" .
                "{$result['fa_trans_type']}:{$result['fa_trans_no']}</a>";
        label_cell($link, "align=center");
        
        // Bank Amount
        amount_cell($result['bank_amount']);
        
        // GL Amount
        if (isset($result['gl_amount'])) {
            amount_cell($result['gl_amount']);
        } else {
            label_cell("N/A");
        }
        
        // Variance
        if (isset($result['variance'])) {
            $variance_class = $result['variance'] > 1.0 ? "class='stockmankobg'" : "";
            label_cell(number_format2($result['variance'], 2), $variance_class);
        } else {
            label_cell("-");
        }
        
        // Status
        if (!$result['valid']) {
            label_cell("<span style='color:red; font-weight:bold;'>FAILED</span>");
        } else {
            label_cell("<span style='color:orange;'>WARNING</span>");
        }
        
        // Details
        $details = "";
        if (!empty($result['errors'])) {
            $details .= "<div class='stockmankobg'><b>Errors:</b><ul>";
            foreach ($result['errors'] as $error) {
                $details .= "<li>" . $error . "</li>";
            }
            $details .= "</ul></div>";
        }
        if (!empty($result['warnings'])) {
            $details .= "<div style='background-color:#fff3cd;'><b>Warnings:</b><ul>";
            foreach ($result['warnings'] as $warning) {
                $details .= "<li>" . $warning . "</li>";
            }
            $details .= "</ul></div>";
        }
        if (!empty($result['suggestions'])) {
            $details .= "<div style='background-color:#d1ecf1;'><b>Suggested Matches:</b><ul>";
            foreach (array_slice($result['suggestions'], 0, 3) as $suggestion) {
                $details .= "<li>Type {$suggestion['type']}:{$suggestion['type_no']} - " . 
                           "Score: {$suggestion['score']}, Amount: " . 
                           number_format2($suggestion['amount'], 2) . "</li>";
            }
            $details .= "</ul></div>";
        }
        label_cell($details);
        
        // Actions
        $actions = submit("FlagTrans[{$result['trans_id']}]", _("Flag for Review"), 
                         false, '', 'default');
        label_cell($actions);
        
        end_row();
    }
    
    end_table(1);
    div_end();
}

/**
 * Display flagged transactions
 */
function display_flagged_transactions($validator)
{
    $flagged = $validator->getFlaggedTransactions();
    
    if (empty($flagged)) {
        display_notification("No transactions are currently flagged for review.");
        return;
    }
    
    echo "<h3>Flagged Transactions (" . count($flagged) . ")</h3>";
    
    start_table(TABLESTYLE2);
    $th = array(
        _("Trans ID"),
        _("Bank"),
        _("Date"),
        _("Amount"),
        _("FA Type:No"),
        _("Memo"),
        _("Reason"),
        _("Actions")
    );
    table_header($th);
    
    foreach ($flagged as $trans) {
        start_row();
        
        label_cell($trans['id']);
        label_cell($trans['bank']);
        label_cell(sql2date($trans['valueTimestamp']));
        amount_cell($trans['transactionAmount']);
        
        $link = "<a href='../../gl/view/gl_trans_view.php?type_id={$trans['fa_trans_type']}&trans_no={$trans['fa_trans_no']}' target='_blank'>" .
                "{$trans['fa_trans_type']}:{$trans['fa_trans_no']}</a>";
        label_cell($link, "align=center");
        
        label_cell($trans['memo']);
        label_cell($trans['matchinfo']);
        
        $actions = submit("ClearFlag[{$trans['id']}]", _("Clear Flag"), 
                         false, '', 'default');
        label_cell($actions);
        
        end_row();
    }
    
    end_table(1);
}

//-----------------------------------------------------------------------------------
// Main processing
//-----------------------------------------------------------------------------------

$validator = new TransactionGLValidator();

// Handle form submissions
if (isset($_POST['validate_all'])) {
    $results = $validator->validateAllTransactions();
    display_validation_results($results);
}

if (isset($_POST['validate_statement'])) {
    $smt_id = $_POST['statement_id'];
    if ($smt_id > 0) {
        $results = $validator->validateAllTransactions($smt_id);
        display_validation_results($results);
    } else {
        display_error("Please select a valid statement.");
    }
}

// Handle flagging transactions
foreach ($_POST as $key => $value) {
    if (strpos($key, 'FlagTrans_') === 0) {
        $trans_id = str_replace('FlagTrans_', '', $key);
        $validator->flagTransactionForReview($trans_id, "Flagged by user during validation");
        display_notification("Transaction $trans_id has been flagged for review.");
    }
    
    if (strpos($key, 'ClearFlag_') === 0) {
        $trans_id = str_replace('ClearFlag_', '', $key);
        $validator->clearFlag($trans_id);
        display_notification("Flag cleared for transaction $trans_id.");
    }
}

//-----------------------------------------------------------------------------------
// Display the page
//-----------------------------------------------------------------------------------

start_form();

// Display flagged transactions first
display_flagged_transactions($validator);

br(2);

// Control panel
start_table(TABLESTYLE_NOBORDER);
start_row();
echo "<td><h3>Validation Controls</h3></td>";
end_row();

start_row();
echo "<td>";
submit('validate_all', _("Validate All Matched Transactions"), true, '', 'default');
echo "</td>";
end_row();

start_row();
echo "<td><b>OR</b> Validate Specific Statement:</td>";
end_row();

start_row();
echo "<td>";

// Get list of statements
$sql = "SELECT s.id, s.bank, s.account, s.smtDate, s.statementId,
               COUNT(t.id) as trans_count,
               SUM(CASE WHEN t.fa_trans_type > 0 AND t.fa_trans_no > 0 THEN 1 ELSE 0 END) as matched_count
        FROM " . TB_PREF . "bi_statements s
        LEFT JOIN " . TB_PREF . "bi_transactions t ON s.id = t.smt_id
        GROUP BY s.id
        ORDER BY s.smtDate DESC
        LIMIT 50";

$result = db_query($sql, "Failed to retrieve statements");

$statements = array();
$statements[0] = "-- Select Statement --";
while ($row = db_fetch($result)) {
    $statements[$row['id']] = "{$row['bank']} - {$row['account']} - " . 
                               sql2date($row['smtDate']) . 
                               " ({$row['matched_count']}/{$row['trans_count']} matched)";
}

echo array_selector('statement_id', null, $statements);
echo "&nbsp;";
submit('validate_statement', _("Validate Selected Statement"), true, '', 'default');

echo "</td>";
end_row();

end_table(1);

br(2);

// Help text
div_start('help_section');
echo "<h4>About This Tool</h4>";
echo "<p>This validation tool checks that imported bank transactions match their associated GL entries:</p>";
echo "<ul>";
echo "<li><b>Missing GL Entries:</b> The specified trans_type and trans_no don't exist in the GL</li>";
echo "<li><b>Amount Mismatches:</b> The bank import amount doesn't match the GL entry amount</li>";
echo "<li><b>Date Warnings:</b> GL entry date is more than 7 days different from bank date</li>";
echo "<li><b>Account Warnings:</b> Expected bank account not found in GL entry</li>";
echo "</ul>";
echo "<p><b>Suggested Matches:</b> When validation fails, the system will suggest possible GL entries that might be the correct match based on amount, date, and memo.</p>";
echo "<p><b>Flagged Transactions:</b> You can flag transactions for manual review. Flagged transactions have status=-1 in the database.</p>";
div_end();

end_form();

//-----------------------------------------------------------------------------------

end_page();

?>
