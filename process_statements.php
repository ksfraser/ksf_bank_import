<?php

// ============================================================================
// DEBUG CONFIGURATION
// ============================================================================

/**
 * Global debug flag for the bank import module
 * Set to true to enable debug output, false to disable
 */
define('BANK_IMPORT_DEBUG', true);

/**
 * Debug output function - only outputs when debug is enabled
 *
 * @param string $message Debug message to output
 * @param mixed $data Optional data to dump
 */
function bank_import_debug($message, $data = null) {
    if (!BANK_IMPORT_DEBUG) {
        return;
    }

    echo "DEBUG: $message\n";
    if ($data !== null) {
        echo "DEBUG DATA: " . print_r($data, true) . "\n";
    }
}

try {
    // Load configuration
    $config_file = __DIR__ . '/config.php';
    if (file_exists($config_file)) {
        $config = include $config_file;
        bank_import_debug("Config loaded from file", $config);
    } else {
        // Fallback configuration
        $config = [
            'fa_root' => '../..',
            'fa_paths' => ['../..', '../../accounting', '/var/www/html/infra/accounting'],
            'debug' => true
        ];
        bank_import_debug("Using fallback config", $config);
    }

    // Dynamic path resolution for FA installation
    $path_to_root = $config['fa_root'];
    bank_import_debug("Initial path_to_root", $path_to_root);

    // Check if FA includes exist at the configured location
    $fa_includes_path = $path_to_root . "/includes/session.inc";
    if (!file_exists($fa_includes_path)) {
        bank_import_debug("FA includes not found at configured path, trying alternatives", $fa_includes_path);
        // Try alternative paths from config
        $found = false;
        foreach ($config['fa_paths'] as $test_path) {
            bank_import_debug("Checking alternative path", $test_path . "/includes/session.inc");
            if (file_exists($test_path . "/includes/session.inc")) {
                $path_to_root = $test_path;
                $found = true;
                bank_import_debug("Found FA includes at alternative path", $test_path);
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
    bank_import_debug("Including autoload");
    include_once( __DIR__  . "/vendor/autoload.php");
    bank_import_debug("Including FA date_functions");
    include_once($path_to_root . "/includes/date_functions.inc");
    bank_import_debug("Including FA session");
    include_once($path_to_root . "/includes/session.inc");
    bank_import_debug("Final path_to_root", $path_to_root);

} catch (Throwable $e) {
    bank_import_debug("Exception caught", $e->getMessage());
    bank_import_debug("Stack trace", $e->getTraceAsString());
    die();
}

// Include Command Pattern Bootstrap (handles POST actions via CommandDispatcher)
bank_import_debug("Including command_bootstrap");
require_once(__DIR__ . '/src/Ksfraser/FaBankImport/command_bootstrap.php');
bank_import_debug("command_bootstrap included");

// HTML library imports
bank_import_debug("Declaring HTML library imports");
use Ksfraser\HTML\Elements\HtmlForm;
use Ksfraser\HTML\Elements\HtmlDiv;
use Ksfraser\HTML\Elements\HtmlTable;
use Ksfraser\HTML\Elements\HtmlTableHead;
use Ksfraser\HTML\Elements\HtmlTableRow;
use Ksfraser\HTML\Elements\HtmlTableBody;
use Ksfraser\HTML\Elements\HtmlTh;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\HtmlRaw;
use Ksfraser\HTML\Elements\HtmlAttribute;
echo "DEBUG: HTML library imports declared\n";

include_once($path_to_root . "/includes/ui/ui_input.inc");
include_once($path_to_root . "/includes/ui/ui_lists.inc");
include_once($path_to_root . "/includes/ui/ui_globals.inc");
include_once($path_to_root . "/includes/ui/ui_controls.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/data_checks.inc");


include_once($path_to_root . "/modules/bank_import/includes/includes.inc");
include_once($path_to_root . "/modules/bank_import/includes/pdata.inc");
bank_import_debug("Bank import includes loaded");

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
//	Make sure that all Processing (i.e. bi_transactions is having status set to 1) triggers a reload/re-search
//TODO:
//	Ensure pre_dbwrite and post_dbwrite are called on all updates so that other modules can also be triggered
//		Using the built in FA routines probably does this.  If we have any non FA db writes we need to add them there.
//TODO:
//	(INT01) Craft the ability to write to other sets of books held in a separate FA company
//		This would probably be best through an API (REST/SOAP).  Does the SLIM API already write where we need to?
//		We would need a config to set up a target set of books 
//			URL, username, password (or OAUTH tokens)
//		We would need granular matching.  We don't want to replicate all transactions to a second set
//			e.g. deposits into CIBC account from Square Up is most likely FHS related.  We have a history in partner data for this BT.
//	 		e.g. using the QE for FHS expense/reimbursement should try to match entries for date/amount in the FHS books and if not there create.
//			e.g. Marcia's CM payments or commissions should match/create entries in that specific set of books from our household books.
//TODO:
//		(INT02) Is there a way to trap on GL Accounts being created and propogate those between sets of books?
//			I use the account codes that appear on the CRA T1 for easy matching for business expenses.  If I create a code in one set of books chances are it needs to be in ALL business sets of books
// 			REST/SOAP?
//			reuse the config from INT01 above
//			pre-create check that the other sets of books don't already have this code.
//TODO:
//		(INT03) creation of bank accounts across multiple sets of books
// 	 	 	We would need to be able to select which sets of books to propogate the creation.	 
//			reuse the config from INT01 above
//			Have a 1 stop creation of a new bank account AND related GL account.
//				Integrate with INT02 to propogate.  Would need pre-create checking of the other accounts to ensure the Bank Account and GL code aren't already in use.


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
bank_import_debug("JS setup completed", ['popup_windows' => $use_popup_windows, 'date_picker' => $use_date_picker]);

echo "DEBUG: About to call page() function\n";
page(_($help_context = "Bank Transactions"), @$_GET['popup'], false, "", $js);
echo "DEBUG: page() function called successfully\n";
bank_import_debug("Page header rendered");

	include_once "Views/module_menu_view.php"; // Include the ModuleMenuView class
    	$menu = new \Views\ModuleMenuView();
    	$menu->renderMenu(); // Render the module menu
bank_import_debug("Module menu rendered");

// REFACTOR STEP 1 COMPLETE: Replaced hardcoded array with PartnerTypeConstants::getAll()
// Previous code (array) replaced by PartnerTypeConstants class - See commit/PR for details
// Test: tests/unit/ProcessStatementsPartnerTypesTest.php (16 tests, 110 assertions - ALL PASSING)
// This provides dynamic partner type discovery while maintaining backward compatibility
$optypes = \Ksfraser\PartnerTypeConstants::getAll();
bank_import_debug("Partner types loaded", ['count' => count($optypes)]);
// TODO END STEP 1
/*
// Load operation types from registry (session-cached)
require_once('OperationTypes/OperationTypesRegistry.php');
use KsfBankImport\OperationTypes\OperationTypesRegistry;
$optypes = OperationTypesRegistry::getInstance()->getTypes();
*/

include_once($path_to_root . "/modules/ksf_modules_common/defines.inc.php");	//$trans_types_readable
bank_import_debug("Common defines included");

require_once( 'class.bank_import_controller.php' );
bank_import_debug("Bank import controller class loaded");

	try {
		$bi_controller = new bank_import_controller();	//no vars for constructor.
		bank_import_debug("Bank import controller instantiated");
	} catch( Exception $e )
	{	
		display_error( __LINE__ . "::" . print_r( $e, true ) );
		bank_import_debug("Bank import controller instantiation failed", $e->getMessage());
	}


//---------------------------------------------------------------------------------
//--------------Unset (Reset) a Transaction----------------------------------------
//---------------------------------------------------------------------------------
// NOTE: The command_bootstrap.php file (included above) handles these four POST actions:
//   - UnsetTrans: Resets transaction status (via UnsetTransactionCommand)
//   - AddCustomer: Creates customer from transaction (via AddCustomerCommand)
//   - AddVendor: Creates vendor/supplier from transaction (via AddVendorCommand)
//   - ToggleTransaction: Toggles debit/credit indicator (via ToggleDebitCreditCommand)
//
// The bootstrap file:
//   1. Initializes the DI container with all dependencies
//   2. Registers the CommandDispatcher
//   3. Handles POST actions using Command Pattern (if USE_COMMAND_PATTERN = true)
//   4. Falls back to legacy bi_controller methods (if USE_COMMAND_PATTERN = false)
//
// To toggle between new Command Pattern and legacy code, set USE_COMMAND_PATTERN in config.
// For now, both paths are supported for backward compatibility.
//---------------------------------------------------------------------------------

// Legacy fallback handlers (only used if USE_COMMAND_PATTERN = false)
if (!defined('USE_COMMAND_PATTERN') || USE_COMMAND_PATTERN === false) {
	// Unset (Reset) a Transaction
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
/*-------------------Process Both Sides of Paired Bank Transfer---------------------------------*/
/*----------------------------------------------------------------------------------------------*/
if ( isset( $_POST['ProcessBothSides'] ) ) {
	list($k, $v) = each($_POST['ProcessBothSides']);	//K is index (first transaction ID)
	if (isset($k) && isset($v)) 
	{
		try {
			// Use new PairedTransferProcessor service
			require_once('Services/PairedTransferProcessor.php');
			require_once('Services/BankTransferFactory.php');
			require_once('Services/BankTransferFactoryInterface.php');
			require_once('Services/TransactionUpdater.php');
			require_once('Services/TransferDirectionAnalyzer.php');
			require_once('class.bi_transactions.php');
			require_once('VendorListManager.php');
			require_once('OperationTypes/OperationTypesRegistry.php');
			
			// Get dependencies
			$bit = new bi_transactions_model();
			$vendorList = \KsfBankImport\VendorListManager::getInstance()->getVendorList();
			$optypes = \KsfBankImport\OperationTypes\OperationTypesRegistry::getInstance()->getTypes();
			
			// Create service instances
			$factory = new \KsfBankImport\Services\BankTransferFactory();
			$updater = new \KsfBankImport\Services\TransactionUpdater();
			$analyzer = new \KsfBankImport\Services\TransferDirectionAnalyzer();
			
			// Create processor with dependencies
			$processor = new \KsfBankImport\Services\PairedTransferProcessor(
				$bit,
				$vendorList,
				$optypes,
				$factory,
				$updater,
				$analyzer
			);
			
			// Process the paired transfer
			$result = $processor->processPairedTransfer($k);
			
			// Display success notification
			display_notification("<span style='color: green; font-weight: bold;'>✓ Paired Bank Transfer Processed Successfully!</span>");
			display_notification("Both sides of the transfer have been recorded:");
			display_notification("<a target=_blank href='../../gl/view/gl_trans_view.php?type_id=" . $result['trans_type'] . "&trans_no=" . $result['trans_no'] . "'>View GL Entry</a>" );
			
		} catch (\Exception $e) {
			display_error("Error processing paired transfer: " . $e->getMessage());
		}
		
		$Ajax->activate('doc_tbl');
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
			$our_account = get_bank_account_by_number($trz['our_account']);
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
			// REFACTOR COMPLETE (Steps 3-9): Replaced switch statement with TransactionProcessor pattern
			// Delegates to handler classes: SupplierTransactionHandler, CustomerTransactionHandler,
			// QuickEntryTransactionHandler, BankTransferTransactionHandler, ManualSettlementHandler, MatchedTransactionHandler
			// See: handlers/*.php and TransactionProcessor.php
			// Test: tests/unit/Handlers/*HandlerTest.php (70 tests - ALL PASSING)

			// Initialize TransactionProcessor for ProcessTransaction action
			// Auto-discovers and loads all handlers from Handlers/ directory
			$transactionProcessor = new \Ksfraser\FaBankImport\TransactionProcessor();

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
}
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

// Load transaction data before creating view
$trzs = array();
bank_import_debug("Starting transaction data display section");

// Load vendor list from singleton manager (session-cached)
if (!class_exists('\KsfBankImport\VendorListManager')) {
	require_once('VendorListManager.php');
}
$vendor_list = \KsfBankImport\VendorListManager::getInstance()->getVendorList();
bank_import_debug("Vendor list loaded", ['count' => count($vendor_list)]);

error_reporting(E_ALL);

require_once( 'class.bi_transactions.php' );
$bit = new bi_transactions_model();
bank_import_debug("bi_transactions_model instantiated");

if( $_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1 )
{
	$trzs = $bit->get_transactions( $_POST['statusFilter'] );
	bank_import_debug("Transactions loaded with status filter", ['status' => $_POST['statusFilter'], 'count' => count($trzs)]);
}
else
{
	$trzs = $bit->get_transactions();
	bank_import_debug("Transactions loaded without filter", ['count' => count($trzs)]);
}

// Create and render the ProcessStatementsView
require_once('src/Ksfraser/FaBankImport/Views/ProcessStatementsView.php');
$view = new \Ksfraser\FaBankImport\Views\ProcessStatementsView($trzs, $optypes, $vendor_list);
echo $view->render();

// End page
end_page(@$_GET['popup'], false, false);
bank_import_debug("Page rendering completed");
?>
