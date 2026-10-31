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
include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/modules/bank_import/includes/banking.php");
include_once($path_to_root . "/modules/bank_import/includes/parsers.inc");
require_once 'includes/qfx_parser.php';

page(_($help_context = "Import Bank Statement"));


        // The menu view of the module is autoloadable.
        $menu = new \Ksfraser\FaBankImport\Views\ModuleMenuView();
        $menu->renderMenu(); // Render the module menu


/**
 * @BABOK FR-IMP-001, FR-IMP-002, FR-IMP-005, FR-IMP-007, FR-IMP-008, FR-IMP-010 | BR-IMP-001 | UAT-IMP-001
 */
function import_statements() {
    start_table(TABLESTYLE);
    start_row();
    echo "<td width=100%><pre>\n";


    echo '<pre>';
    $statements = unserialize($_SESSION['statements']);
    foreach($statements as $id => $smt) {
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

/**//*******************************************************
* Import the statements
*
* @param array statements
* @return string summary of import (errors, imports, updates)
*************************************************************/
/**
 * @BABOK FR-IMP-007, FR-IMP-008, FR-IMP-010 | BR-IMP-001 | UAT-IMP-001
 */
function importStatement($smt) 
{
		//display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $smt, true ) );
	$message = '';
/** Moving to namespaces **/
	require_once(  './class.bi_statements.php' );
	$bis = new bi_statements_model();
/*
	$bis = new BiStatements();
**/
	$bis->set( "bank", $smt->bank );
	$bis->set( "statementId", $smt->statementId );
	$exists = $bis->statement_exists();
	$bis->obj2obj( $smt );

	if( ! $exists )
	{
			display_notification( __FILE__ . "::" . __LINE__ . ":: Statement Doesn't Exist.  Inserting" );
		$sql = $bis->hand_insert_sql();
		$res = db_query($sql, "could not insert transaction");
    		$smt_id = db_insert_id();
		$bis->set( "id", $smt_id );
			display_notification( __FILE__ . "::" . __LINE__ . "Inserted Statement $smt_id" );
    		$message .= "new, imported";
	} else 
	{
			//display_notification( __FILE__ . "::" . __LINE__ . "Statement Exists.  Updating" );
		$bis->update_statement();
			display_notification( "Updated Statement $smt->statementId " );
			//display_notification( __FILE__ . "::" . __LINE__ . "Updated Statement $smt->statementId " );
    		$message .= "existing, updated";
	}
	//$smt_id = $bis->get( "statementId" );
	$smt_id = $bis->get( "id" );
/* */
	$newinserted=0;
	$dupecount=0;
	$dupeupdated=0;
/** Moving to Namespaces **/
	require_once( 'class.bi_transactions.php' );
/**/
	foreach($smt->transactions as $id => $t) 
	{
		display_notification(  "Processing transaction" );
		//display_notification( __FILE__ . "::" . __LINE__ . "Processing transaction" );
		set_time_limit( 0 );	//Don't time out in php.  Apache might still kill us...
		//var_dump( __FILE__ . "::" . __LINE__ );
		try {
		//var_dump( __FILE__ . "::" . __LINE__ );
			unset( $bit );
		//var_dump( __FILE__ . "::" . __LINE__ );
			try 
			{
/** Moving to namespaces */
				$bit = new bi_transactions_model();
/**
				$bit = new BiTransactions();
*/
			} catch( Exception $e )
			{
				display_error( __FILE__ . "::" . __LINE__ . print_r( $e, true ) );
			}
		//var_dump( __FILE__ . "::" . __LINE__  );
		//var_dump( $bit );
		} catch( Exception $e )
		{
			display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $e, true ) );
		}
		//var_dump( __FILE__ . "::" . __LINE__ );
		$bit->trz2obj( $t );
		$bit->set( "smt_id", $smt_id );
		$dupe = $bit->trans_exists();
		//var_dump( __FILE__ . "::" . __LINE__ );
		if( $dupe )
		{
			display_notification( __FILE__ . "::" . __LINE__ . " Transaction Exists for statement: $smt_id:" . $bit->get( "accountName" ) );
			//display_notification( __FILE__ . "::" . __LINE__ . " Transaction Exists for statement: $smt_id::" . print_r( $bit, true ) );
			$dupecount++;
/**
 * Mantis 2948
 * Don't re-insert duplicate.
 * Update in certain cases.  Handled within bi_transactions_model
 */
			//trans_exists sets the variables out of the DB
			//$bit->update( $t );
			/*
			  if( $bit->update( $t ) )
			  {
				$dupeupdated++;
			  }
			*/
			
/* ! 2948 */
		}
		else
		{
		//var_dump( __FILE__ . "::" . __LINE__ );
			$sql = $bit->hand_insert_sql();
			$res = db_query($sql, "could not insert transaction");
			$t_id = db_insert_id();
			display_notification( __FILE__ . "::" . __LINE__ . " Inserted transaction: $t_id " );
			$newinserted++;
		}
		//var_dump( __FILE__ . "::" . __LINE__ );
	}	//foreach statement
	$message .= ' ' . count($smt->transactions) . ' transactions';
			display_notification( __FILE__ . "::" . __LINE__ . " Inserted transactions: $newinserted " );
			display_notification( __FILE__ . "::" . __LINE__ . " Duplicates Total: $dupecount " );
			display_notification( __FILE__ . "::" . __LINE__ . " Updated Duplicates: $dupeupdated " );
	return $message;
/* */
}	//import_statement fc


/**
 * @BABOK FR-IMP-001, FR-IMP-002, FR-IMP-003 | BR-IMP-001 | UAT-IMP-001
 */
function do_upload_form() {
    $parsers = array();
    $_parsers = getParsers();
    foreach($_parsers as $pid => $pdata) {
	$parsers[$pid] = $pdata['name'];
    }


    div_start('doc_tbl');
    start_table(TABLESTYLE);
    $th = array(_("Select File(s) and type"), '');
    table_header($th);

    label_row(_("Format:"), array_selector('parser', null, $parsers, array('select_submit' => true)));
    foreach($_parsers[$_POST['parser']]['select'] as $param => $label) {

	switch($param) {
	    case 'bank_account':
		bank_accounts_list_row($label, 'bank_account', $selected_id=null, $submit_on_change=false);
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


/**
 * @BABOK FR-IMP-005, FR-IMP-006 | BR-IMP-001 | UAT-IMP-001
 */
function parse_uploaded_files() {
    start_table(TABLESTYLE);
    start_row();
    

    echo "<td width=100%><pre>\n";

    // initialize parser class
    $parserClass = $_POST['parser'] . '_parser';
    $parser = new $parserClass;

    //prepare static data for parser
    $static_data = array();
    $_parsers = getParsers();
    //display_notification( print_r( $_POST ) );
    foreach($_parsers[$_POST['parser']]['select'] as $param => $label) {
	switch($param) {
	    case 'bank_account':
		//get bank account data
		$bank_account = get_bank_account($_POST['bank_account']);
    //display_notification( __FILE__ . "::" . __LINE__ . "::" . "Bank Account Details from get_bank_account for Bank passed in from form::" .  print_r( $bank_account, true ) );
		$static_data['account'] = $bank_account['bank_account_number'];
		$static_data['account_number'] = $bank_account['bank_account_number'];
		$static_data['currency'] = $bank_account['bank_curr_code'];
		$static_data['account_code'] = $bank_account['account_code'];
		$static_data['account_type'] = $bank_account['account_type'];
		$static_data['account_name'] = $bank_account['bank_account_name'];
		$static_data['bank_charge_act'] = $bank_account['bank_charge_act'];
		//$static_data['raw'] = $bank_account;

	    break;
	}
    }

    $smt_ok = 0;
    $trz_ok = 0;
    $smt_err = 0;
    $trz_err = 0;
    $multistatements = array();
    $uploaded_filenames = array();

    foreach($_FILES['files']['name'] as $id=>$fname) {
	if ($fname === '' || $fname === null) {
	    continue;
	}
	$uploaded_filenames[$id] = $fname;
    	display_notification( __FILE__ . "::" . __LINE__ . "  Processing file `$fname` with format `{$_parsers[$_POST['parser']]['name']}`" );
    	//echo  __FILE__ . "::" . __LINE__ . "Processing file `$fname` with format `{$_parsers[$_POST['parser']]['name']}`...\n";

    	$content = file_get_contents($_FILES['files']['tmp_name'][$id]);

	$bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (strncmp($content, $bom, 3) === 0) {
            $content = substr($content, 3);
        }

    	$statements = $parser->parse($content, $static_data, $debug=false); // false for no debug, true for debug
	$multistatements[$id] = $statements;
	if( $debug )
	{
		var_dump( __FILE__ . "::" . __LINE__ . ":: Statements post parsing"  );
		var_dump( $statements );
	}

	foreach ($statements as $smt) {
	    echo "statement: {$smt->statementId}:";
	    if ($smt->validate($debug = false)) {
		    $smt_ok ++;
		    $trz_cnt = count($smt->transactions);
		    $trz_ok += $trz_cnt;
		    echo " is valid, $trz_cnt transactions\n";
	    } else {
		    echo " is invalid!!!!!!!!!\n";
		    $smt->validate($debug=true);
		    $smt_err ++;
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

    echo '</td>';
    end_row();

    end_table(1);
    hidden('parser', $_POST['parser']);
    if ($smt_err == 0) {
	if (offer_account_resolution_step($multistatements, $uploaded_filenames) === false) {
	    // Nothing is unresolved: the import is offered on the stored statements.
	    flatten_statements_into_session($multistatements);
	    submit_center_last('import', 'Import');
	}
    }
}

/**
 * The statements that remain after the whole upload flow, flattened for the import.
 *
 * @param array<int, array<int|string, object>> $multistatements File index => statements
 * @return void
 * @BABOK FR-IMP-001, FR-IMP-003, FR-IMP-007 | BR-IMP-001 | UAT-IMP-001
 */
function flatten_statements_into_session(array $multistatements): void
{
    $statements = [];

    foreach ($multistatements as $fileStatements) {
        foreach ($fileStatements as $smt) {
            $statements[] = $smt;
        }
    }

    $_SESSION['statements'] = serialize($statements);
}

/**
 * The saved detected-account => FA account associations the user remembered earlier.
 *
 * @param array<int, array<int|string, object>> $multistatements File index => statements
 * @return array<string, string> The detected account id => FA bank account number
 * @BABOK FR-002, FR-005, FR-006 | BR-ACCT-001 | UAT-ACCT-001
 */
function load_saved_account_associations(array $multistatements): array
{
    $mapping = new \Ksfraser\FaBankImport\Service\StatementAccountMappingService();
    $repo = new \Ksfraser\FaBankImport\Repository\DatabaseConfigRepository();

    $detectedAll = [];
    foreach ($mapping->collectDetectedAccountsByFile($multistatements) as $detectedList) {
        foreach ($detectedList as $detected) {
            $detectedAll[$detected] = true;
        }
    }

    $resolved = [];
    foreach (array_keys($detectedAll) as $detected) {
        $key = \Ksfraser\FaBankImport\Service\DetectedAccountAssociationKey::forDetectedAccount((string) $detected);
        $bankAccountId = $repo->get($key);
        if ($bankAccountId === null || $bankAccountId === '' || (int) $bankAccountId <= 0) {
            continue;
        }
        $ba = get_bank_account((int) $bankAccountId);
        if (is_array($ba) && !empty($ba['bank_account_number'])) {
            $resolved[$detected] = $ba['bank_account_number'];
        }
    }

    return $resolved;
}


/**
 * Run the account resolution step after the upload.
 *
 * Auto-applies the saved associations first; whatever is still unresolved
 * gets the blocking resolution screen and no Import button (FR-002, FR-006).
 *
 * @param array<int, array<int|string, object>> $multistatements File index => statements
 * @param array<int, string>                    $uploaded_filenames File index => uploaded file name
 * @return bool True when the blocking screen was rendered
 * @BABOK FR-002, FR-003, FR-005, FR-006, FR-008 | BR-ACCT-001 | UAT-ACCT-001
 */
function offer_account_resolution_step(array $multistatements, array $uploaded_filenames): bool
{
    $resolution = new \Ksfraser\FaBankImport\Service\AccountResolutionService(
        new \Ksfraser\FaBankImport\Service\StatementAccountMappingService(),
        new \Ksfraser\FaBankImport\Repository\FaBankAccountDirectory()
    );

    $savedMap = load_saved_account_associations($multistatements);
    $multistatements = $resolution->applySavedAssociations($multistatements, $savedMap);

    $unresolved = $resolution->collectUnresolved($multistatements, $savedMap);
    if ($unresolved === []) {
        return false;
    }

    $_SESSION[\Ksfraser\FaBankImport\Views\AccountResolutionView::SESSION_KEY] = serialize($multistatements);

    $view = new \Ksfraser\FaBankImport\Views\AccountResolutionView();
    $view->renderBlocking($unresolved, $resolution->availableBankAccounts(), $uploaded_filenames);

    return true;
}

/**
 * Handle the Proceed button of the resolution screen.
 *
 * @return void
 * @BABOK FR-002, FR-003, FR-005, FR-006 | BR-ACCT-001 | UAT-ACCT-001
 */
function resolve_account_mappings(): void
{
    start_table(TABLESTYLE);
    start_row();
    echo "<td width=100%><pre>\n";

    if (empty($_SESSION[\Ksfraser\FaBankImport\Views\AccountResolutionView::SESSION_KEY])) {
        display_error(_("No pending account resolution session found. Please upload the file(s) again."));
        echo "</pre></td>";
        end_row();
        end_table(1);
        return;
    }

    $multistatements = unserialize($_SESSION[\Ksfraser\FaBankImport\Views\AccountResolutionView::SESSION_KEY]);
    $decisions = \Ksfraser\FaBankImport\Views\AccountResolutionView::collectSubmittedMapping($_POST);

    $accounts = (new \Ksfraser\FaBankImport\Service\AccountResolutionService(
        new \Ksfraser\FaBankImport\Service\StatementAccountMappingService(),
        new \Ksfraser\FaBankImport\Repository\FaBankAccountDirectory()
    ))->availableBankAccounts();
    $accountById = [];
    foreach ($accounts as $account) {
        $accountById[$account['id']] = $account;
    }

    $detectedToAccountNumber = [];
    $remembered = [];

    foreach ($decisions as $decision) {
        if ($decision['detected'] === '') {
            continue;
        }
        if ($decision['selected'] <= 0) {
            display_error(_("Please select a FrontAccounting bank account for detected account") . ': ' . htmlspecialchars($decision['detected']));
            continue;
        }
        if (!isset($accountById[$decision['selected']])) {
            display_error(_("Invalid bank account selection for detected account") . ': ' . htmlspecialchars($decision['detected']));
            continue;
        }

        $detectedToAccountNumber[$decision['detected']] = $accountById[$decision['selected']]['number'];

        if ($decision['remember']) {
            $remembered[$decision['detected']] = $decision['selected'];
        }
    }

    if ($detectedToAccountNumber === []) {
        echo "</pre></td>";
        end_row();
        end_table(1);
        return;
    }

    $resolution = new \Ksfraser\FaBankImport\Service\AccountResolutionService(
        new \Ksfraser\FaBankImport\Service\StatementAccountMappingService(),
        new \Ksfraser\FaBankImport\Repository\FaBankAccountDirectory()
    );
    $multistatements = $resolution->applyResolution($multistatements, $detectedToAccountNumber);

    $repo = new \Ksfraser\FaBankImport\Repository\DatabaseConfigRepository();
    foreach ($remembered as $detected => $selectedId) {
        $key = \Ksfraser\FaBankImport\Service\DetectedAccountAssociationKey::forDetectedAccount($detected);
        $username = null;
        $currentUser = isset($_SESSION['wa_current_user']) ? $_SESSION['wa_current_user'] : null;
        if (is_object($currentUser) && property_exists($currentUser, 'name')) {
            $username = $currentUser->name;
        } elseif (is_array($currentUser) && isset($currentUser['name'])) {
            $username = $currentUser['name'];
        }
        $repo->set($key, (string) $selectedId, $username, 'Associate detected account to FA bank account');
    }

    echo "Resolved detected accounts successfully.\n";
    echo "</pre></td>";
    end_row();
    start_row();
    echo '<td>';
    submit_center_first('goback', 'Go back');
    submit_center_last('import', 'Import');
    echo '</td>';
    end_row();
    end_table(1);

    flatten_statements_into_session($multistatements);
    unset($_SESSION[\Ksfraser\FaBankImport\Views\AccountResolutionView::SESSION_KEY]);
}

/**
 * Handle the Cancel button of the resolution screen.
 *
 * @return void
 * @BABOK FR-002, FR-005 | BR-ACCT-001 | UAT-ACCT-001
 */
function cancel_account_resolution(): void
{
    unset($_SESSION[\Ksfraser\FaBankImport\Views\AccountResolutionView::SESSION_KEY]);
}







// select changed
if (get_post('_parser_update')) {
	$Ajax->activate('doc_tbl');
}

start_form(true);

if (empty($_POST['upload']) && empty($_POST['import']) && empty($_POST['resolve_accounts']) && empty($_POST['cancel_account_resolution'])) {
    do_upload_form();
}


//if upload is hit, parse the files and store result in session
if (@$_POST['upload'] && ($_FILES['files']['error'][0] == 0)) {
    parse_uploaded_files();
}

//if import is hit, perform the import
if (@$_POST['import']) {
    import_statements();
}

//if the resolution Proceed button is hit, apply the chosen mappings
if (@$_POST['resolve_accounts']) {
    resolve_account_mappings();
}

//if the resolution Cancel button is hit, drop the pending resolution
if (@$_POST['cancel_account_resolution']) {
    cancel_account_resolution();
    do_upload_form();
}


end_form(2);

end_page();
?>
