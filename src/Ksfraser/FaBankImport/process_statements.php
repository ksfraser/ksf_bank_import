<?php

$path_to_root = "../..";
$page_security = 'SA_SALESTRANSVIEW';
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/ui/ui_input.inc");
include_once($path_to_root . "/includes/ui/ui_lists.inc");
include_once($path_to_root . "/includes/ui/ui_globals.inc");
include_once($path_to_root . "/includes/ui/ui_controls.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/data_checks.inc");


include_once($path_to_root . "/modules/bank_import/includes/includes.inc");
include_once($path_to_root . "/modules/bank_import/includes/pdata.inc");

//20240316
//	QE is working.
//	BT is working.

//TODO:
//	Audit routine to ensure that all processed entries match what they are allocated to
//		For example if an entry says it matches JE XXX, ensure that the dates are close, and the amount is exact.
//	Audit that no 2 transactions point to the same type+number.  i.e. recurring payments aren't matched to the same payment.
//		During the insert/update we should make sure this dupe doesn't pre-exist before doing the update.
//	Make sure that all Processing (i.e. bi_transactions is having status set to 1) triggers a reload/re-search
//	Ensure pre_dbwrite and post_dbwrite are called on all updates so that other modules can also be triggered
//		Looking to have related sets of books (i.e. business specific ones) to be matched/updated/created
//			i.e. Marcia's CM payments or commissions should match/create entries in that specific set of books from our household books.
//			i.e. FHS Expenditures and Payments should match/create entries in the FHS books.
//		Is there a way to trap on GL Accounts being created and propogate those between sets of books?
//		If so, is there a way to also trap creation of bank accounts?
//	Add a filter on the search screen to filter by "our account"


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Bank Transactions"), @$_GET['popup'], false, "", $js);


	include_once "Views/module_menu_view.php"; // Include the ModuleMenuView class
    	$menu = new \Views\ModuleMenuView();
    	$menu->renderMenu(); // Render the module menu

// REFACTOR STEP 1 COMPLETE: Replaced hardcoded array with PartnerTypeConstants::getAll()
// Previous code (array) replaced by PartnerTypeConstants class - See commit/PR for details
// Test: tests/unit/ProcessStatementsPartnerTypesTest.php (16 tests, 110 assertions - ALL PASSING)
// This provides dynamic partner type discovery while maintaining backward compatibility
$optypes = \Ksfraser\PartnerTypeConstants::getAll();
// TODO END STEP 1

include_once($path_to_root . "/modules/ksf_modules_common/defines.inc.php");	//$trans_types_readable

require_once( 'class.bank_import_controller.php' );
	try {
		$bi_controller = new bank_import_controller();	//no vars for constructor.
	} catch( Exception $e )
	{	
		display_error( __LINE__ . "::" . print_r( $e, true ) );
	}

// Initialize Transaction Processor - auto-discovers and loads all handlers from Handlers/ directory
use Ksfraser\FaBankImport\TransactionProcessor;

$transactionProcessor = new TransactionProcessor();

//---------------------------------------------------------------------------------
//--------------Unset (Reset) a Transaction----------------------------------------
//---------------------------------------------------------------------------------
// actions
unset($k, $v);

if( isset( $_POST['UnsetTrans'] ) )
{
	$bi_controller->unsetTrans();
}

/*----------------------------------------------------------------------------------------------*/
/*------------------------Add Customer----------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/

	 //display_notification( __LINE__ );
if (isset($_POST['AddCustomer'])) 
{
	$bi_controller->addCustomer();
}
/*----------------------------------------------------------------------------------------------*/
/*-------------------Add Vendor-----------------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/
	 //display_notification( __LINE__ );
if (isset($_POST['AddVendor'])) 
{
	$bi_controller->addVendor();
}
	 //display_notification( __FILE__ . "::" . __LINE__ );
if (isset($_POST['ToggleTransaction'])) 
{
	$bi_controller->toggleDebitCredit();
	display_notification( __LINE__ . "::" .  print_r( $_POST, true ));
}
/*----------------------------------------------------------------------------------------------*/
/*-------------------Process Transaction--------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/
if ( isset( $_POST['ProcessTransaction'] ) ) {
//20240208 EACH is depreciated.  Should rewrite with foreach
	list($k, $v) = each($_POST['ProcessTransaction']);	//K is index.  V is "process/..."
	if (isset($k) && isset($v) && isset($_POST['partnerType'][$k])) 
	{
		//check params
		$error = 0;
		if ( ! isset( $_POST["partnerId_$k"] ) ) 
		{
			$Ajax->activate('doc_tbl');
			display_error('missing partnerId');
			$error = true;
		}
	
		if (!$error) {
			$tid = $k;
			//time to gather data about transaction
			//load $tid
			$trz = get_transaction($tid);
			//display_notification('<pre>'.print_r($trz,true).'</pre>');
	
			//check bank account
			$our_account = get_bank_account_by_number($trz['our_account']);
			if (empty($our_account)) 
			{
				$Ajax->activate('doc_tbl');
				display_error(  __FILE__ . "::" . __LINE__ . "::" . ' the bank account <b>'.$trz['our_account'].'</b> is not defined in Bank Accounts');
				$error = 1;
			}
			//display_notification('<pre>'.print_r($our_account,true).'</pre>');
		}
		if (!$error) {
/*Charges*/
			//get charges
			$chgs = array();
			$_cids = array_filter(explode(',', $_POST['cids'][$tid]));
			foreach($_cids as $cid) {
				$chgs[] = get_transaction($cid);
			}
			//display_notification("tid=$tid, cids=`".$_POST['cids'][$tid]."`");
			//display_notification("cids_array=".print_r($_cids,true));
	
		//now sum up
		//now group data from tranzaction
			$amount = $trz['transactionAmount'];
/**
*			$charge = 0;
*			foreach($chgs as $t) 
*			{
*				$charge += $t['transactionAmount'];
*			}
*/
			$charge = $bi_controller->charge = $bi_controller->sumCharges( $tid );
			$bi_controller->set( "charge", $charge );
/*! Charges*/
	
			//display_notification("amount=$amount, charge=$charge");
			//display_notification("partnerType=".$_POST['partnerType'][$k]);
			$pid = "partnerId_" . $k;
			//display_notification( "partner=".$_POST[ $pid ] );
			$partnerId = $_POST[ $pid ];
			$bi_controller->set( "partnerId", $partnerId );
	
				//display_notification( __FILE__ . "::" . __LINE__ );
//These are needed for SP.  The others too???
			$bi_controller->set( "trz", $trz );
			$bi_controller->set( "tid", $tid );
			$bi_controller->set( "our_account", $our_account );

			// REFACTOR COMPLETE (Steps 3-9): Replaced switch statement with TransactionProcessor pattern
			// Delegates to handler classes: SupplierTransactionHandler, CustomerTransactionHandler,
			// QuickEntryTransactionHandler, BankTransferTransactionHandler, ManualSettlementHandler, MatchedTransactionHandler
			// See: handlers/*.php and TransactionProcessor.php
			// Test: tests/unit/Handlers/*HandlerTest.php (70 tests - ALL PASSING)
			
			try {
				$partnerType = $_POST['partnerType'][$k];
				$collectionIds = implode(',', array_filter(explode(',', $_POST['cids'][$tid] ?? '')));
				
				// Process transaction using appropriate handler
				$result = $transactionProcessor->process(
					$partnerType,
					$trz,              // Database transaction data
					$_POST,            // Form POST data
					$tid,              // Transaction ID
					$collectionIds,    // Charge transaction IDs
					$our_account       // Our bank account
				);
				
				// Display result using TransactionResult's display() method
				$result->display();
				
				// Display transaction links if available
				if ($result->isSuccess() && $result->getTransNo() > 0) {
					$transNo = $result->getTransNo();
					$transType = $result->getTransType();
					
					display_notification("<a target='_blank' href='../../gl/view/gl_trans_view.php?type_id={$transType}&trans_no={$transNo}'>View GL Entry</a>");
					
					// Special handling for customer payments (ST_CUSTPAYMENT = 12)
					if ($transType == 12) {
						display_notification("<a target='_blank' href='../../sales/view/view_receipt.php?type_id={$transType}&trans_no={$transNo}'>View Payment and Associated Invoice</a>");
					}
				}
				
			} catch (\InvalidArgumentException $e) {
				display_error("No handler registered for partner type: {$_POST['partnerType'][$k]}");
			} catch (\Exception $e) {
				display_error("Error processing transaction: " . $e->getMessage());
			}
			// END REFACTOR
			
			$Ajax->activate('doc_tbl');
		} //end of if !error

	} // end of is set....
} //end of is isset(post[processTranzaction])

/*----------------------------------------------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/

/*
// check whether a transaction is ignored
unset($k, $v);
list($k, $v) = each($_POST['IgnoreTrans']);
if (isset($k) && isset($v)) {
		//display_notification( __FILE__ . "::" . __LINE__ );
	updateTrans($_POST['trans_id'][$k], $_POST['charge_id'][$k], TR_MAN_SETTLED);
	$Ajax->activate('doc_tbl');
	display_notification('Manually processed');
}
*/
/************************************************************************************************************************/
/**********************************************  GUI  *******************************************************************/
/************************************************************************************************************************/

// TODO REFACTOR STEP 10: Move all HTML rendering below to ProcessStatementsView class
// Should use existing HTML components for clean separation of concerns
// Test: tests/unit/Views/ProcessStatementsViewTest.php

// search button pressed
if (get_post('RefreshInquiry')) {
	$Ajax->activate('doc_tbl');
}

//SC: check whether a customer has been changed, so that we can update branch as well
// as there a user can click on one submit button only, there is no need for multiple check
unset($k, $v);
if (isset($_POST['partnerId'])) {
			//display_notification( __FILE__ . "::" . __LINE__ );
	list($k, $v) = each($_POST['partnerId']);
	if (isset($k) && isset($v)) {
		$Ajax->activate('doc_tbl');
	}
}

//SC: 05.10.2012: whether post['partnerType'] exists, refresh
if (isset($_POST['partnerType'])) {
	$Ajax->activate('doc_tbl');
}


start_form();

div_start('doc_tbl');
$custinv = array();

if (1) {
	//------------------------------------------------------------------------------------------------
	// this is filter table

	require_once( 'header_table.php' );
	$headertable = new ksf_modules_table_filter_by_date();
	$headertable->bank_import_header();

	//if (!@$_GET['popup'])
	//	end_form();


/*************************************************************************************************************/
/***********************************  Transactions  **********************************************************/
/*************************************************************************************************************/
	//------------------------------------------------------------------------------------------------
	// this is data display table
	$trzs = array();
	$vendor_list = get_vendor_list();	//array

	error_reporting(E_ALL);

	require_once( 'class.bi_transactions.php' );
	$bit = new bi_transactions_model();
	if( $_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1 )
	{
		$trzs = $bit->get_transactions( $_POST['statusFilter'] );
	}
	else
	{
		$trzs = $bit->get_transactions();
	}
	
/*************************************************************************************************************/
	start_table(TABLESTYLE, "width='100%'");
	table_header(array("Transaction Details", "Operation/Status"));

	//load data
	
	//This foreach loop should probably be rolled up into the WHILE loop above.
	// REFACTOR STEP 2 COMPLETE: bi_lineitem accepts PartnerTypeConstants via $optypes
	// Test: tests/unit/BiLineitemPartnerTypesTest.php (13 tests, 80 assertions)
	foreach($trzs as $trz_code => $trz_data) 
	{
		//try to match something, interpreting saved info if status=TR_SETTLED
		//$minfo = doMatching($myrow, $coyBankAccounts, $custBankAccounts, $suppBankAccounts);
	
		//now group data from tranzaction
		$tid = 0;
		$cids = array();
	
		//bring trans details
		$has_trz = 0;
		$amount = 0;
		$charge = 0;
	
		foreach($trz_data as $idx => $trz) 
		{
//LOGIC ERROR?
//We are handling line items, but then ->display out of the loop?
//I assume this is for lines with charges, etc which could be in the MT940 format but not QFX and therefore I'm not seeing  an issue?
			require_once( 'class.bi_lineitem.php' );
			// REFACTOR STEP 2 COMPLETE: bi_lineitem now receives PartnerTypeConstants via $optypes
			// $optypes = PartnerTypeConstants::getAll() (set on line 54)
			// Test: tests/unit/BiLineitemPartnerTypesTest.php (13 tests, 80 assertions - ALL PASSING)
			// NOTE: Fixed PartnerType labels (MA, ZZ) to match legacy array for backward compatibility
			$bi_lineitem = new bi_lineitem( $trz, $vendor_list, $optypes );
		}	//foreach trz_data

		//cids is an empty array at this point.
		$cids = implode(',', $cids);
		$bi_lineitem->display();

 		//Now part of lineitem->display
               //$arr_arr = find_matching_existing( $trz, $bank );


	} //Foreach
	end_table();
/*************************************************************************************************************/
}

div_end();
end_form();

end_page(@$_GET['popup'], false);
?>
