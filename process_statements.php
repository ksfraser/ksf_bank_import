<?php

// Ensure Composer autoloader is loaded for trait/class autoloading
require_once __DIR__ . '/vendor/autoload.php';

// Prevent conditional-cache responses (304) that can break legacy jsHttpRequest flows.
if (!headers_sent()) {
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache');
	header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
}

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
require_once(__DIR__ . '/src/Ksfraser/HTML/Ajax/DivActivator.php');

//20240316
//	QE is working.
//	BT is working.

/**
 * Safely activate Ajax refresh target when FA Ajax object is available.
 */
function activate_doc_tbl_safe(): void
{
	\Ksfraser\HTML\Ajax\DivActivator::activateDocTable();
}

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


if (!isset($use_popup_windows)) $use_popup_windows = false;
if (!isset($use_date_picker)) $use_date_picker = false;
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


//=========================================================================
// POST ACTION DISPATCHER - Phase 4 Refactoring Integration
// Replaces 300+ lines of if/elseif statements with pluggable dispatcher
//=========================================================================
// Handles all POST actions through registered action handlers:
// - UnsetTrans, AddCustomer, AddVendor, ToggleTransaction
// - RunTransferMatcher, RunTransferAudits, ProcessBothSides
// - ProcessTransaction (main transaction processing)
//
// Benefits:
// - Cognitive complexity reduced from O(n) branches to O(1) dispatch
// - New actions add easily: create class + register (no controller changes)
// - Each action encapsulated in single responsibility class
// - Strategy pattern (TransactionProcessor) integrated seamlessly
//=========================================================================

try {
	require_once __DIR__ . '/src/Ksfraser/FaBankImport/Actions/Registry/ActionRegistrar.php';
	
	// Create fully initialized dispatcher with all actions registered
	$actionDispatcher = \Ksfraser\FaBankImport\Actions\Registry\ActionRegistrar::createDispatcher();
	
	// Dispatch POST request to first matching action
	$actionDispatcher->dispatch($_POST);
	
} catch (\Throwable $e) {
	// Log dispatcher errors but don't break page rendering
	error_log('POST Action Dispatcher error: ' . $e->getMessage());
	if (function_exists('display_error')) {
		display_error('An unexpected error occurred processing your request. Please try again.');
	}
}

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
	$k = null;
	$v = null;
	if (is_array($_POST['partnerId']) && !empty($_POST['partnerId'])) {
		reset($_POST['partnerId']);
		$k = key($_POST['partnerId']);
		$v = current($_POST['partnerId']);
	}
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

	// TODO [Phase-0-review]: Moved to Shared kernel - use Ksfraser\FaBankImport\Shared\Entities\Transaction
	// Old: require_once(__DIR__ . '/class.bi_transactions.php');
	require_once(__DIR__ . '/class.bi_transactions.php'); // Temp: keep for compatibility
	
	// For Phase 0 migration, keeping bi_transactions_model but marked for transition
	$bit = new bi_transactions_model();
	$fetchStartedAt = microtime(true);
	if( isset($_POST['statusFilter']) && ($_POST['statusFilter'] == 0 OR $_POST['statusFilter'] == 1) )
	{
		$trzs = $bit->get_transactions( $_POST['statusFilter'] );
	}
	else
	{
		$trzs = $bit->get_transactions();
	}
	$fetchDurationMs = (int)round((microtime(true) - $fetchStartedAt) * 1000);
	error_log('[bank_import] process_statements fetch_transactions_ms=' . $fetchDurationMs
		. ' statusFilter=' . (isset($_POST['statusFilter']) ? (string)$_POST['statusFilter'] : 'null')
		. ' from=' . (isset($_POST['TransAfterDate']) ? (string)$_POST['TransAfterDate'] : 'null')
		. ' to=' . (isset($_POST['TransToDate']) ? (string)$_POST['TransToDate'] : 'null')
		. ' bank=' . (isset($_POST['bankAccountFilter']) ? (string)$_POST['bankAccountFilter'] : 'ALL')
		. ' groups=' . (is_array($trzs) ? (string)count($trzs) : '0')
	);
	
/*************************************************************************************************************/
	start_table(TABLESTYLE, "width='100%'");
	table_header(array("Transaction Details", "Operation", "Partner/Processing", "Matching GLs"));

	//load data
	
	//This foreach loop should probably be rolled up into the WHILE loop above.
	$renderStartedAt = microtime(true);
	$renderedRows = 0;
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
	
		// TODO [Phase-0-review]: Moved to Shared kernel - use Ksfraser\FaBankImport\Shared\Entities\LineItem
		// Old: require_once(__DIR__ . '/class.bi_lineitem.php');
		require_once(__DIR__ . '/class.bi_lineitem.php'); // Temp: keep for compatibility
		
		foreach($trz_data as $idx => $trz) 
		{
			// TODO: Transition to use Shared\Entities\LineItem
			$bi_lineitem = new bi_lineitem( $trz, $vendor_list, $optypes );
			
			// =====================================================================
			// Phase 3: Cascade BankAccountMapping to transaction (NEW)
			// =====================================================================
			try {
				// Extract mapping from parent statement
				$stmtMapping = null;
				if (isset($trz['smt_id']) && !empty($trz['smt_id'])) {
					$bis = new BiStatements();
					$stmtData = $bis->get_statement_by_id($trz['smt_id']);
					if (is_array($stmtData)) {
						$stmtMapping = $bis->extractBankAccountMapping();
					}
				}
				
				// Cascade mapping to transaction
				if ($stmtMapping && !empty($stmtMapping->bankid) && !empty($stmtMapping->acctid)) {
					// Determine FA account ID (from statement or configuration)
					$faAccountId = $stmtData['fa_bank_account_id'] ?? 1;
					
					// Update transaction's mapping reference (idempotent operation)
					$bit->storeBankAccountMapping($stmtMapping, $faAccountId);
				}
			} catch (\Throwable $e) {
				// Non-blocking: Mapping cascade failure should not block transaction display
				@error_log('ProcessStatements: BankAccountMapping cascade failed: ' . $e->getMessage());
			}
			
			// Display each line item in the loop
			$bi_lineitem->display();
			$renderedRows++;
		}	//foreach trz_data
	/*
	*	//cids is an empty array at this point.
	*	$cids = implode(',', $cids);
	*/
	} //Foreach
	$renderDurationMs = (int)round((microtime(true) - $renderStartedAt) * 1000);
	error_log('[bank_import] process_statements render_rows_ms=' . $renderDurationMs . ' rows=' . $renderedRows);
	end_table();
/*************************************************************************************************************/
}

div_end();
end_form();

// End page
end_page(@$_GET['popup'], false, false);
?>
