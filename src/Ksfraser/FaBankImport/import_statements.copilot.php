<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
    Released under the terms of the GNU General Public License, GPL,
    as published by the Free Software Foundation, either version 3
    of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_BANKACCOUNT';
$path_to_root = "../..";

// Session and UI includes
include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

// Module includes
include_once($path_to_root . "/modules/bank_import/includes/banking.php");
include_once($path_to_root . "/modules/bank_import/includes/parsers.inc");

page(_($help_context = "Import Bank Statement"));

function import_statements()
{
    start_table(TABLESTYLE);
    start_row();
    echo "<td width=100%><pre>\n";

    echo '<pre>';
    $statements = unserialize($_SESSION['statements']);
    foreach ($statements as $id => $smt) {
        echo "importing statement {$smt->statementId} ...";
        echo importStatement($smt);
        echo "\n";
    }
    echo '</pre>';

    echo "</pre></td>";
    end_row();
    start_row();
    echo '<td>';
    submit_center_first('goback', 'Go back');
    echo '</td>';
    end_row();
    end_table(1);
    hidden('parser', $_POST['parser']);
}

/**
 * Import the statements
 *
 * @param object $smt
 * @return string summary of import (errors, imports, updates)
 */
function importStatement($smt)
{
    $message = '';
    require_once('./class.bi_statements.php');
    $bis = new bi_statements_model();
    $bis->set("bank", $smt->bank);
    $bis->set("statementId", $smt->statementId);
    $exists = $bis->statement_exists();
    $bis->obj2obj($smt);

    if (!$exists) {
        display_notification(__FILE__ . "::" . __LINE__ . ":: Statement Doesn't Exist. Inserting");
        $sql = $bis->hand_insert_sql();
        $res = db_query($sql, "could not insert transaction");
        $statementId = db_insert_id();
        $bis->set("id", $statementId);
        display_notification(__FILE__ . "::" . __LINE__ . "Inserted Statement $statementId");
        $message .= "new, imported";
    } else {
        $bis->update_statement();
        display_notification("Updated Statement $smt->statementId");
        $message .= "existing, updated";
    }

    $statementId = $bis->get("id");
    $newInserted = 0;
    $dupeCount = 0;
    $dupeUpdated = 0;
    require_once('class.bi_transactions.php');
    foreach ($smt->transactions as $id => $transaction) {
        $result = processTransaction($transaction, $statementId);
        if ($result == "duplicate") {
            $dupeCount++;
        } else if ($result == "new") {
            $newInserted++;
        }
    }
    $message .= ' ' . count($smt->transactions) . ' transactions';
    display_notification(__FILE__ . "::" . __LINE__ . " Inserted transactions: $newInserted");
    display_notification(__FILE__ . "::" . __LINE__ . " Duplicates Total: $dupeCount");
    display_notification(__FILE__ . "::" . __LINE__ . " Updated Duplicates: $dupeUpdated");
    return $message;
}

function processTransaction($transaction, $statementId)
{
    display_notification("Processing transaction");
    try {
        $bit = new bi_transactions_model();
    } catch (Exception $e) {
        logError("Failed to create bi_transactions_model: " . print_r($e, true));
        display_error("An error occurred while processing the transaction.");
        return null;
    }
    $bit->trz2obj($transaction);
    $bit->set("smt_id", $statementId);
    $dupe = $bit->trans_exists();
    if ($dupe) {
        display_notification("Transaction Exists for statement: $statementId:" . $bit->get("accountName"));
        return "duplicate";
    } else {
        $sql = $bit->hand_insert_sql();
        $res = db_query($sql, "could not insert transaction");
        $t_id = db_insert_id();
        display_notification("Inserted transaction: $t_id");
        return "new";
    }
}

function logError($message)
{
    // Implement logging functionality here
    error_log($message, 3, "/var/log/ksf_bank_import_errors.log");
}

function do_upload_form()
{
    $parsers = array();
    $_parsers = getParsers();
    foreach ($_parsers as $pid => $pdata) {
        $parsers[$pid] = $pdata['name'];
    }

    div_start('doc_tbl');
    start_table(TABLESTYLE);
    $th = array(_("Select File(s) and type"), '');
    table_header($th);

    label_row(_("Format:"), array_selector('parser', null, $parsers, array('select_submit' => true)));
    foreach ($_parsers[$_POST['parser']]['select'] as $param => $label) {
        switch ($param) {
            case 'bank_account':
                bank_accounts_list_row($label, 'bank_account', $selected_id = null, $submit_on_change = false);
                break;
        }
    }
    label_row(_("Files"), "<input type='file' name='files[]' multiple />");

    start_row();
    label_cell('Upload', "class='label'");
    submit_cells('upload', _("Upload"));
    end_row();

    end_table(1);
    div_end();
}

function parse_uploaded_files()
{
    start_table(TABLESTYLE);
    start_row();

    echo "<td width=100%><pre>\n";

    // initialize parser class
    $parserClass = $_POST['parser'] . '_parser';
    $parser = new $parserClass;

    // prepare static data for parser
    $static_data = array();
    $_parsers = getParsers();
    foreach ($_parsers[$_POST['parser']]['select'] as $param => $label) {
        switch ($param) {
            case 'bank_account':
                // get bank account data
                $bank_account = get_bank_account($_POST['bank_account']);
                $static_data['account'] = $bank_account['bank_account_number'];
                $static_data['account_number'] = $bank_account['bank_account_number'];
                $static_data['currency'] = $bank_account['bank_curr_code'];
                $static_data['account_code'] = $bank_account['account_code'];
                $static_data['account_type'] = $bank_account['account_type'];
                $static_data['account_name'] = $bank_account['bank_account_name'];
                $static_data['bank_charge_act'] = $bank_account['bank_charge_act'];
                break;
        }
    }

    $smt_ok = 0;
    $trz_ok = 0;
    $smt_err = 0;
    $trz_err = 0;

    foreach ($_FILES['files']['name'] as $id => $fname) {
        display_notification(__FILE__ . "::" . __LINE__ . "  Processing file `$fname` with format `{$_parsers[$_POST['parser']]['name']}`");

        $content = file_get_contents($_FILES['files']['tmp_name'][$id]);

        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (strncmp($content, $bom, 3) === 0) {
            $content = substr($content, 3);
        }

        $statements = $parser->parse($content, $static_data, $debug = false); // false for no debug, true for debug

        foreach ($statements as $smt) {
            echo "statement: {$smt->statementId}:";
            if ($smt->validate($debug = false)) {
                $smt_ok++;
                $trz_cnt = count($smt->transactions);
                $trz_ok += $trz_cnt;
                echo " is valid, $trz_cnt transactions\n";
            } else {
                echo " is invalid!!!!!!!!!\n";
                $smt->validate($debug = true);
                $smt_err++;
            }
        }

        echo "======================================\n";
        echo "Valid statements   : $smt_ok\n";
        echo "Invalid statements : $smt_err\n";
        echo "Total transactions : $trz_ok\n";
    }
    echo "</pre></td>";

    end_row();
    start_row();
    echo '<td>';
    submit_center_first('goback', 'Go back');
    if ($smt_err == 0)
        submit_center_last('import', 'Import');

    echo '</td>';
    end_row();

    end_table(1);
    hidden('parser', $_POST['parser']);
    if ($smt_err == 0) {
        $_SESSION['statements'] = serialize($statements);
    }
}

// select changed
if (get_post('_parser_update')) {
    $Ajax->activate('doc_tbl');
}

start_form(true);

if (empty($_POST['upload']) && empty($_POST['import'])) {
    do_upload_form();
}

// if upload is hit, parse the files and store result in session
if (@$_POST['upload'] && ($_FILES['files']['error'][0] == 0)) {
    parse_uploaded_files();
}

// if import is hit, perform the import
if (@$_POST['import']) {
    import_statements();
}

end_form(2);

end_page();
?>
