<?php
/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */
$page_security = 'SA_BANKACCOUNT';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/modules/bank_import/includes/banking.php");
include_once($path_to_root . "/modules/bank_import/includes/parsers.inc");
require_once 'qfx_parser.php';
include_once "views/module_menu_view.php"; // Include the ModuleMenuView class

page(_($help_context = "Import Bank Statement"));

function import_statements() {
    $menu = new \Views\ModuleMenuView();
    $menu->renderMenu(); // Render the module menu

    start_table(TABLESTYLE);
    start_row();
    echo "<td width=100%><pre>\n";

    echo '<pre>';
    $statements = unserialize($_SESSION['statements']);
    foreach ($statements as $id => $smt) {
        echo "importing statement {$smt->statementId} ...";

        // Use the factory to create the appropriate parser
        try {
            $parser = QfxParserFactory::createParser($smt->rawContent);
            $parser->parse($smt->rawContent, $smt->staticData);
            echo "Statement imported successfully.\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

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
function importStatement($smt) 
{
		//display_notification( __FILE__ . "::" . __LINE__ . ":" . print_r( $smt, true ) );
	$message = '';
	$bis = new BiStatements();
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
				$bit = new BiTransactions();
			} catch( Exception $e )
			{
				display_error( __FILE__ . "::" . __LINE__ . print_r( $e, tru ) );
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

    foreach($_FILES['files']['name'] as $id=>$fname) {
    	display_notification( __FILE__ . "::" . __LINE__ . "  Processing file `$fname` with format `{$_parsers[$_POST['parser']]['name']}`" );
    	//echo  __FILE__ . "::" . __LINE__ . "Processing file `$fname` with format `{$_parsers[$_POST['parser']]['name']}`...\n";

    	$content = file_get_contents($_FILES['files']['tmp_name'][$id]);

	$bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (strncmp($content, $bom, 3) === 0) {
            $content = substr($content, 3);
        }

    	$statements = $parser->parse($content, $static_data, $debug=false); // false for no debug, true for debug
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


//if upload is hit, parse the files and store result in session
if (@$_POST['upload'] && ($_FILES['files']['error'][0] == 0)) {
    parse_uploaded_files();
}

//if import is hit, perform the import
if (@$_POST['import']) {
    import_statements();
}


end_form(2);

end_page();
?>
