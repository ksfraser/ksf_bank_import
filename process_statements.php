<?php

// Ensure relative paths resolve from this module directory (FA expects $path_to_root to be a web-relative path).
chdir(__DIR__);

// Load configuration
$config_file = __DIR__ . '/config.php';
if (file_exists($config_file)) {
    $config = include $config_file;
} else {
    // Fallback configuration
    $config = [
        'fa_root' => '../..',
        'fa_paths' => ['../..', '../../accounting', '/var/www/html/infra/accounting'],
        'debug' => true
    ];
}

// Dynamic path resolution for FA installation
$path_to_root = $config['fa_root'];

// Check if FA includes exist at the configured location
$fa_includes_path = $path_to_root . "/includes/session.inc";
if (!file_exists($fa_includes_path)) {
    // Try alternative paths from config
    $found = false;
    foreach ($config['fa_paths'] as $test_path) {
		// Never use absolute filesystem paths for $path_to_root; it is used to build web URLs (CSS/JS/images).
		if (preg_match('/^[A-Za-z]:\\\\|^\//', $test_path)) {
			continue;
		}
		if (file_exists($test_path . "/includes/session.inc")) {
			$path_to_root = $test_path;
			$found = true;
			break;
		}
    }

    // If still not found, provide helpful error
    if (!$found) {
        if ($config['debug']) {
            die("ERROR: FrontAccounting includes not found. Please check your config.php file and ensure FA_ROOT points to a valid FrontAccounting installation. Tried paths: " . implode(', ', $config['fa_paths']) . ". Create config.php from config.example.php");
        } else {
            die("System configuration error. Please contact administrator.");
        }
    }
}

$page_security = 'SA_SALESTRANSVIEW';
include_once( __DIR__  . "/vendor/autoload.php");
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
//TODO:
//	Audit that no 2 transactions point to the same type+number.
// 		i.e. recurring payments aren't matched to the same payment.
//			During the insert/update we should make sure this dupe doesn't pre-exist before doing the update.
//TODO:
//	Craft the ability to write to other sets of books held in a separate FA company
//	This would probably be best through an API (REST/SOAP).


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Bank Transactions"), @$_GET['popup'], false, "", $js);


	$moduleMenuView = __DIR__ . '/views/module_menu_view.php';
	if (!is_file($moduleMenuView)) {
		$moduleMenuView = __DIR__ . '/Views/module_menu_view.php';
	}
	include_once $moduleMenuView;
    	$menu = new \Views\ModuleMenuView();
    	$menu->renderMenu();

$optypes = array(
	'SP' => 'Supplier',
	'CU' => 'Customer',
	'QE' => 'Quick Entry',
	'BT' => 'Bank Transfer',
	'MA' => 'Manual settlement',
	'ZZ' => 'Matched',
);

// Enhancement: auto-discover partner types when registry is available, while
// preserving legacy hardcoded defaults for production/baseline compatibility.
if (class_exists('\\Ksfraser\\PartnerTypes\\PartnerTypeRegistry')) {
	$registry = \Ksfraser\PartnerTypes\PartnerTypeRegistry::getInstance();
	$discoveredOptypes = array();
	foreach ($registry->getAll() as $partnerType) {
		$discoveredOptypes[$partnerType->getShortCode()] = $partnerType->getLabel();
	}
	if (!empty($discoveredOptypes)) {
		$optypes = $discoveredOptypes;
	}
}

include_once($path_to_root . "/modules/ksf_modules_common/defines.inc.php");	//$trans_types_readable


require_once( 'class.bank_import_controller.php' );
	try {
		$bi_controller = new bank_import_controller();	//no vars for constructor.
	} catch( Exception $e )
	{	
		display_error( __LINE__ . "::" . print_r( $e, true ) );
	}


//---------------------------------------------------------------------------------
//--------------Unset (Reset) a Transaction----------------------------------------
//---------------------------------------------------------------------------------
// actions
//---------------------------------------------------------------------------------

unset($k, $v);
if( isset( $_POST['UnsetTrans'] ) )
{
	$bi_controller->unsetTrans();
}
// require_once __DIR__ . '/src/Ksfraser/FaBankImport/Actions/UnsetTransactionAction.php';
// (new \Ksfraser\FaBankImport\Actions\UnsetTransactionAction())->execute($_POST, $bi_controller);

/*----------------------------------------------------------------------------------------------*/
/*------------------------Add Customer----------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/

	 //display_notification( __LINE__ );
if (isset($_POST['AddCustomer'])) 
{
	$bi_controller->addCustomer();
}
// require_once __DIR__ . '/src/Ksfraser/FaBankImport/Actions/AddCustomerAction.php';
// (new \Ksfraser\FaBankImport\Actions\AddCustomerAction())->execute($_POST, $bi_controller);
/*----------------------------------------------------------------------------------------------*/
/*-------------------Add Vendor-----------------------------------------------------------------*/
/*----------------------------------------------------------------------------------------------*/
	 //display_notification( __LINE__ );
if (isset($_POST['AddVendor'])) 
{
	$bi_controller->addVendor();
}
// require_once __DIR__ . '/src/Ksfraser/FaBankImport/Actions/AddVendorAction.php';
// (new \Ksfraser\FaBankImport\Actions\AddVendorAction())->execute($_POST, $bi_controller);
	 //display_notification( __FILE__ . "::" . __LINE__ );
if (isset($_POST['ToggleTransaction'])) 
{
	$bi_controller->toggleDebitCredit();
	display_notification( __LINE__ . "::" .  print_r( $_POST, true ));
}
// require_once __DIR__ . '/src/Ksfraser/FaBankImport/Actions/ToggleTransactionAction.php';
// (new \Ksfraser\FaBankImport\Actions\ToggleTransactionAction())->execute($_POST, $bi_controller);
// Paired-transfer dual-side POST action extracted to SRP class.
if (isset($_POST['ProcessBothSides'])) {
	require_once __DIR__ . '/src/Ksfraser/FaBankImport/Actions/PairedTransferDualSideAction.php';
	$pairedTransferAction = new \Ksfraser\FaBankImport\Actions\PairedTransferDualSideAction();
	if ($pairedTransferAction->supports($_POST)) {
		$pairedTransferAction->dispatchToUi($_POST);
	}
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
		        $bit = new bi_transactions_model();
        		$trz = $bit->get_transaction( $tid );
				//Setting internal so we can refactor further later to use Object rather than Array
        			//$trz = $bit->get_transaction( $tid, true );

			//check bank account
			$our_account = fa_get_bank_account_by_number($trz['our_account']);
			if (empty($our_account)) 
			{
				$Ajax->activate('doc_tbl');
				display_error(  __FILE__ . "::" . __LINE__ . "::" . ' the bank account <b>'.$trz['our_account'].'</b> is not defined in Bank Accounts');
				$error = 1;
			}
		}
		if (!$error) {
/*Charges*/
			//get charges
			$chgs = array();
//How are CIDS set in the first place?
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

			// Stage 3-9 enhancement recovered:
			// Prefer TransactionProcessor (strategy handlers) when available,
			// but preserve legacy switch dispatch as compatibility fallback.
			$processedByStrategy = false;
			if (class_exists('\\Ksfraser\\FaBankImport\\TransactionProcessor')) {
				try {
					$transactionProcessor = new \Ksfraser\FaBankImport\TransactionProcessor();
					$partnerType = $_POST['partnerType'][$k];
					$collectionIds = implode(',', array_filter(explode(',', $_POST['cids'][$tid] ?? '')));
					$result = $transactionProcessor->process(
						$partnerType,
						$trz,
						$_POST,
						(int)$tid,
						$collectionIds,
						$our_account
					);

					if (is_object($result) && method_exists($result, 'display')) {
						$result->display();
					}

					if (class_exists('\\Ksfraser\\FA\\Notifications\\TransactionResultLinkPresenter')) {
						$linkPresenter = new \Ksfraser\FA\Notifications\TransactionResultLinkPresenter();
						$linkPresenter->displayFromResult($result, is_array($config) ? $config : [], (string)$partnerType);
					}

					$processedByStrategy = true;
				} catch (\Throwable $e) {
					if (function_exists('display_warning')) {
						display_warning('TransactionProcessor strategy fallback: ' . $e->getMessage());
					} elseif (function_exists('display_notification')) {
						display_notification('TransactionProcessor strategy fallback: ' . $e->getMessage());
					}
					$processedByStrategy = false;
				}
			}

			if (!$processedByStrategy) {
				switch(true)
				{
					case ($_POST['partnerType'][$k] == 'SP'):
						$bi_controller->processSupplierTransaction();
						break;
					case ($_POST['partnerType'][$k] == 'CU'):
						// Legacy CU inline markers retained for production-baseline compatibility:
						// $trans_type = ST_CUSTPAYMENT;
						// ST_BANKDEPOSIT
						// ST_CUSTPAYMENT
						$bi_controller->processCustomerPayment();
						break;
					case ($_POST['partnerType'][$k] == 'QE'):
						// Delegate to legacy controller workflow which contains full QE handling.
						$bi_controller->processTransactions();
						break;
					case ($_POST['partnerType'][$k] == 'BT'):
						// Delegate to legacy controller workflow which contains full BT handling.
						$bi_controller->processTransactions();
						break;
					case ($_POST['partnerType'][$k] == 'MA'):
						// Delegate to legacy controller workflow which contains full MA handling.
						$bi_controller->processTransactions();
						break;
					case ($_POST['partnerType'][$k] == 'ZZ'):
						// Delegate to legacy controller workflow which contains full ZZ handling.
						$bi_controller->processTransactions();
						break;
					default:
						break;
				}
			}
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

	require_once(__DIR__ . '/header_table.php');
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
	
	$vendor_list = array();
	$vendorListManagerFile = __DIR__ . '/VendorListManager.php';
	if (is_file($vendorListManagerFile)) {
		require_once $vendorListManagerFile;
		if (class_exists('\\KsfBankImport\\VendorListManager')) {
			try {
				$vendor_list = \KsfBankImport\VendorListManager::getInstance()->getVendorList();
			} catch (\Throwable $e) {
				$vendor_list = array();
			}
		}
	}

	error_reporting(E_ALL);

	require_once(__DIR__ . '/class.bi_transactions.php');
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
	foreach($trzs as $trz_code => $trz_data) 
	{
		//try to match something, interpreting saved info if status=TR_SETTLED
		//$minfo = doMatching($myrow, $coyBankAccounts, $custBankAccounts, $suppBankAccounts);
	/*
	*	//now group data from tranzaction
	*	$tid = 0;
	*	$cids = array();
	*
	*	//bring trans details
	*	$has_trz = 0;
	*	$amount = 0;
	*	$charge = 0;
	*/
	
		require_once(__DIR__ . '/class.bi_lineitem.php');
		foreach($trz_data as $idx => $trz) 
		{
			//LOGIC ERROR?
				//We are handling line items, but then ->display out of the loop?
				//I assume this is for lines with charges, etc which could be in the MT940 format but not QFX and therefore I'm not seeing  an issue?
			$bi_lineitem = new bi_lineitem( $trz, $vendor_list, $optypes );
		}	//foreach trz_data
	/*
	*	//cids is an empty array at this point.
	*	$cids = implode(',', $cids);
	*/
		$bi_lineitem->display();
	} //Foreach
	end_table();
/*************************************************************************************************************/
}

div_end();
end_form();

// End page
end_page(@$_GET['popup'], false, false);
?>
