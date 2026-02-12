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

// Ensure all relative includes resolve from this module directory.
chdir(__DIR__);

$page_security = 'SA_BANKACCOUNT';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/modules/bank_import/includes/banking.php");
include_once($path_to_root . "/modules/bank_import/includes/parsers.inc");
require_once __DIR__ . '/includes/qfx_parser.php';

// Mantis #2708: File upload management - Refactored (Phase 2)
require_once __DIR__ . '/vendor/autoload.php';
use Ksfraser\FaBankImport\Service\FileUploadService;
use Ksfraser\FaBankImport\ValueObject\FileInfo;
use Ksfraser\FaBankImport\Repository\DatabaseConfigRepository;
use Ksfraser\FaBankImport\Service\StatementAccountMappingService;
use Ksfraser\FaBankImport\Service\DetectedAccountAssociationKey;
use Ksfraser\FaBankImport\Service\ImportRunLogger;
use Ksfraser\FaBankImport\Service\BankImportPathResolver;

//TODO Migrate to use HTML classes

page(_($help_context = "Import Bank Statement"));

		include_once __DIR__ . "/views/module_menu_view.php"; // Include the ModuleMenuView class
        $menu = new \Views\ModuleMenuView();
        $menu->renderMenu(); // Render the module menu

function import_statements() {
    start_table(TABLESTYLE);
    start_row();
    echo "<td width=100%><pre>\n";

    echo '<pre>';
/** 20250716 add in capability to import multiple files at once **/
    $multistatements = unserialize($_SESSION['multistatements']);
    // Mantis #2708: Get uploaded file IDs from session
    $uploaded_file_ids = isset($_SESSION['uploaded_file_ids']) ? $_SESSION['uploaded_file_ids'] : array();

	$logPath = isset($_SESSION['bank_import_run_log_path']) ? (string)$_SESSION['bank_import_run_log_path'] : '';
	$logFileName = $logPath !== '' ? basename($logPath) : '';
	if ($logFileName !== '' && preg_match('/^import_run_[0-9]{8}_[0-9]{6}_[a-zA-Z0-9_-]{6,64}\.jsonl$/', $logFileName) !== 1) {
		$logFileName = '';
	}

	$logger = bank_import_get_logger();
	$fileCount = is_array($multistatements) ? count($multistatements) : 0;
	$statementCount = 0;
	if (is_array($multistatements)) {
		foreach ($multistatements as $list) {
			$statementCount += is_array($list) ? count($list) : 0;
		}
	}
	bank_import_log_event($logger, 'import.started', [
		'file_count' => (int)$fileCount,
		'statement_count' => (int)$statementCount,
	]);
	$importedStatements = 0;
    
	foreach( $multistatements as $file_index => $statements )
	{
	    // Get file ID for this set of statements
	    $file_id = isset($uploaded_file_ids[$file_index]) ? $uploaded_file_ids[$file_index] : null;
	    
	    foreach($statements as $id => $smt) {
		echo "importing statement {$smt->statementId} ...";
		echo importStatement($smt, $file_id, $logger);  // Pass file_id
		$importedStatements++;
		echo "\n";
	    }
	}
	bank_import_log_event($logger, 'import.completed', [
		'statements_processed' => (int)$importedStatements,
	]);
    echo '</pre>';
    echo "</pre></td>";
    end_row();
    start_row();
    echo '<td>';
	submit_center_first('goback', 'Go back');
	$canViewLogs = true;
	if (function_exists('has_access') && isset($_SESSION['wa_current_user']) && isset($_SESSION['wa_current_user']->access)) {
		$canViewLogs = has_access($_SESSION['wa_current_user']->access, 'SA_BANKIMPORTLOGVIEW');
	}
	if ($canViewLogs && $logFileName !== '' && $logPath !== '' && @is_file($logPath)) {
		echo " <a class='button' href='view_import_logs.php?file=" . urlencode($logFileName) . "'>" . _("View Import Log") . "</a>";
	}
    echo '</td>';
    end_row();
    end_table(1);
    hidden('parser', $_POST['parser']);
	unset($_SESSION['bank_import_run_log_path']);
}

/**//*******************************************************
* Import the statements
*
* @param array statements
* @param int|null file_id Optional uploaded file ID to link
* @return string summary of import (errors, imports, updates)
*************************************************************/
function importStatement($smt, $file_id = null) 
{
	$message = '';
	$logger = func_num_args() >= 3 ? func_get_arg(2) : null;

	// Currency should come from the OFX/QFX statement (bank-provided).
	// We only use FrontAccounting's bank account currency as a *fallback* when the
	// statement currency is missing/blank. If there is a mismatch, we log it but do not
	// override the bank-provided value.
	try {
		$faAccountNumber = isset($smt->account) ? trim((string)$smt->account) : '';
		$faBankAccountId = $faAccountNumber !== '' ? fa_get_bank_account_id_by_number($faAccountNumber) : null;
		if ($faBankAccountId !== null) {
			$ba = get_bank_account((int)$faBankAccountId);
			$faCurrency = is_array($ba) && isset($ba['bank_curr_code']) ? trim((string)$ba['bank_curr_code']) : '';
			$ofxCurrency = isset($smt->currency) ? trim((string)$smt->currency) : '';

			// Fallback only when statement currency is missing.
			if ($ofxCurrency === '' && $faCurrency !== '') {
				$smt->currency = $faCurrency;
				if (isset($smt->transactions) && is_array($smt->transactions)) {
					foreach ($smt->transactions as $t) {
						if (is_object($t) && (!isset($t->currency) || trim((string)$t->currency) === '')) {
							$t->currency = $faCurrency;
						}
					}
				}
				bank_import_log_event($logger, 'statement.currency_fallback_applied', [
					'file_id' => $file_id !== null ? (int)$file_id : null,
					'fa_bank_account_id' => (int)$faBankAccountId,
					'fa_bank_account_number' => $faAccountNumber,
					'fa_currency' => $faCurrency,
				]);
			} elseif ($faCurrency !== '' && $ofxCurrency !== '' && $faCurrency !== $ofxCurrency) {
				bank_import_log_event($logger, 'statement.currency_mismatch', [
					'file_id' => $file_id !== null ? (int)$file_id : null,
					'fa_bank_account_id' => (int)$faBankAccountId,
					'fa_bank_account_number' => $faAccountNumber,
					'ofx_currency' => $ofxCurrency,
					'fa_currency' => $faCurrency,
				]);
			}
		}
	} catch (\Throwable $e) {
		// Never block import flow on currency normalization.
	}
/** Moving to namespaces **/
	require_once(__DIR__ . '/class.bi_statements.php');
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
		//display_notification( __FILE__ . "::" . __LINE__ . ":: Statement Doesn't Exist.  Inserting" );
		$sql = $bis->hand_insert_sql();
		$res = db_query($sql, "could not insert transaction");
    		$smt_id = db_insert_id();
		$bis->set( "id", $smt_id );
//20250716 Remove logging of insertion
		//display_notification( __FILE__ . "::" . __LINE__ . "Inserted Statement $smt_id" );
    		$message .= "new, imported";
	} else 
	{
		//display_notification( __FILE__ . "::" . __LINE__ . "Statement Exists.  Updating" );
		$bis->update_statement();
		display_notification( "Updated Statement $smt->statementId " );
    		$message .= "existing, updated";
	}
	$smt_id = $bis->get( "id" );
	bank_import_log_event($logger, 'statement.upserted', [
		'statement_id' => (int)$smt_id,
		'statement_identifier' => (string)($smt->statementId ?? ''),
		'existed' => (bool)$exists,
		'file_id' => $file_id !== null ? (int)$file_id : null,
	]);
/* */
	
	// Mantis #2708: Link uploaded file to statement (Phase 2 refactored)
	if ($file_id !== null) {
		try {
			$uploadService = FileUploadService::create();
			$uploadService->linkToStatements($file_id, array($smt_id));
			bank_import_log_event($logger, 'statement.file_linked', [
				'statement_id' => (int)$smt_id,
				'file_id' => (int)$file_id,
			]);
		} catch (\Exception $e) {
			display_error("Failed to link file to statement: " . $e->getMessage());
			bank_import_log_event($logger, 'statement.file_link_failed', [
				'statement_id' => (int)$smt_id,
				'file_id' => (int)$file_id,
				'error' => $e->getMessage(),
			]);
		}
	}
	
	$newinserted=0;
	$dupecount=0;
	$dupeupdated=0;
/** Moving to Namespaces **/
	require_once(__DIR__ . '/class.bi_transactions.php');
/**/
	foreach($smt->transactions as $id => $t) 
	{
		display_notification(  "Processing transaction" );
		//display_notification( __FILE__ . "::" . __LINE__ . "Processing transaction" );
		set_time_limit( 0 );	//Don't time out in php.  Apache might still kill us...
		try {
			unset( $bit );
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
		} catch( Exception $e )
		{
			display_notification( __FILE__ . "::" . __LINE__ . " " . print_r( $e, true ) );
		}
		$bit->trz2obj( $t );
		$bit->set( "smt_id", $smt_id );
		// Ensure posting-time bank association metadata is available per transaction.
		if (isset($smt->acctid) && ($bit->get('acctid') === null || trim((string)$bit->get('acctid')) === '')) {
			$bit->set('acctid', $smt->acctid);
		}
		if (isset($smt->bankid)) {
			$bit->set('bankid', $smt->bankid);
		}
		if (isset($smt->intu_bid)) {
			$bit->set('intu_bid', $smt->intu_bid);
		}
		$dupe = $bit->trans_exists();
		if( $dupe )
		{
//20250716 Remove logging of existing (dupe)
			//display_notification( __FILE__ . "::" . __LINE__ . " Transaction Exists for statement: $smt_id:" . $bit->get( "accountName" ) );
			//display_notification( __FILE__ . "::" . __LINE__ . " Transaction Exists for statement: $smt_id::" . print_r( $bit, true ) );
			$dupecount++;
/**
 * Mantis 2948
 * Don't re-insert duplicate.
 * Update in certain cases.  Handled within bi_transactions_model
 */
			//trans_exists sets the variables out of the DB
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
			$sql = $bit->hand_insert_sql();
			$res = db_query($sql, "could not insert transaction");
			$t_id = db_insert_id();
//20250716 Remove logging of insertion
			//display_notification( __FILE__ . "::" . __LINE__ . " Inserted transaction: $t_id " );
			$newinserted++;
		}
	}	//foreach statement
	$message .= ' ' . count($smt->transactions) . ' transactions';
			display_notification( __FILE__ . "::" . __LINE__ . " Inserted transactions: $newinserted " );
			display_notification( __FILE__ . "::" . __LINE__ . " Duplicates Total: $dupecount " );
			display_notification( __FILE__ . "::" . __LINE__ . " Updated Duplicates: $dupeupdated " );
			bank_import_log_event($logger, 'statement.transactions_summary', [
				'statement_id' => (int)$smt_id,
				'statement_identifier' => (string)($smt->statementId ?? ''),
				'transactions_total' => is_array($smt->transactions) ? count($smt->transactions) : 0,
				'inserted' => (int)$newinserted,
				'duplicates' => (int)$dupecount,
				'duplicates_updated' => (int)$dupeupdated,
			]);
	return $message;
/* */
}	//import_statement fc

//Initial draft of a CLASS to replace the body of this function
//is in Ksfraser\FaBankImport\Views\ImportUploadForm
//TODO migrate to use the class.
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
		// Bank account selection is resolved post-parse (per-statement) using detected account mapping.
		// Keeping this commented out during UAT in case we reverse course.
		// bank_accounts_list_row($label, 'bank_account', $selected_id=null, $submit_on_change=false);
	    break;

	}
    }
	label_row(_("Bank Account"), "<span class='smalltext'>" . _("Determined from file (per statement) using saved account mappings.") . "</span>");
    label_row(_("Files"), "<input type='file' name='files[]' multiple />");

	label_row(
		_("If duplicates are detected"),
		"<label><input type='checkbox' name='force_upload_all' value='1'> "
			. _("Upload anyway (force re-upload)")
			. "</label><br><span class='smalltext'>" . _("When checked, duplicate warnings will be bypassed for all selected files.") . "</span>"
	);


    start_row();
    label_cell('Upload', "class='label'");
    submit_cells('upload', _("Upload"));
    end_row();

    end_table(1);
    div_end();
}

function bank_import_log_dir(): string
{
	return BankImportPathResolver::forCurrentCompany()->logsDir();
}

function bank_import_get_logger(): ?ImportRunLogger
{
	if (empty($_SESSION['bank_import_run_log_path'])) {
		return null;
	}
	$logPath = (string)$_SESSION['bank_import_run_log_path'];
	try {
		return ImportRunLogger::resume($logPath);
	} catch (\Throwable $e) {
		return null;
	}
}

/**
 * @param array<string,mixed> $context
 */
function bank_import_log_event(?ImportRunLogger $logger, string $eventName, array $context = []): void
{
	if ($logger === null) {
		return;
	}
	try {
		$logger->event($eventName, $context);
	} catch (\Throwable $e) {
		// Never block import flow on logging.
	}
}


function parse_uploaded_files() {
    start_table(TABLESTYLE);
    start_row();
    

    echo "<td width=100%><pre>\n";

    // Mantis #2708: Initialize file upload service (Phase 2 refactored)
    $uploadService = FileUploadService::create();
	$uploaded_file_ids = [];
	$uploaded_filenames = [];
	$has_blocked_duplicates = false;  // Track if any files were blocked
	$pending_duplicates = [];         // Track duplicates requiring user decision
	$force_upload_all = !empty($_POST['force_upload_all']);

	// Import Run Audit Log (start a new run)
	$logger = null;
	try {
		$logger = ImportRunLogger::start(bank_import_log_dir());
		$_SESSION['bank_import_run_log_path'] = $logger->getLogPath();
		bank_import_log_event($logger, 'run.started', [
			'parser' => (string)($_POST['parser'] ?? ''),
			'bank_account_id' => isset($_POST['bank_account']) ? (int)$_POST['bank_account'] : null,
			'force_upload_all' => (bool)$force_upload_all,
			'file_count' => isset($_FILES['files']['name']) && is_array($_FILES['files']['name']) ? count($_FILES['files']['name']) : 0,
		]);
	} catch (\Throwable $e) {
		$logger = null;
		unset($_SESSION['bank_import_run_log_path']);
	}

    // initialize parser class
    $parserClass = $_POST['parser'] . '_parser';
    $parser = new $parserClass;

    //prepare static data for parser
    $static_data = array();
    $_parsers = getParsers();
    foreach($_parsers[$_POST['parser']]['select'] as $param => $label) {
	switch($param) {
	    case 'bank_account':
		if (empty($_POST['bank_account'])) {
			break;
		}
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
	$statements = array();

	if (!isset($_FILES['files']) || !isset($_FILES['files']['name']) || !is_array($_FILES['files']['name'])) {
		display_error(_("No files were uploaded. Please choose at least one file."));
		echo "</pre></td>";
		end_row();
		end_table(1);
		return;
	}


    foreach($_FILES['files']['name'] as $id=>$fname) {
		if ($fname === '' || $fname === null) {
			continue;
		}
		$uploaded_filenames[$id] = $fname;
		bank_import_log_event($logger, 'file.begin', [
			'file_index' => (int)$id,
			'filename' => (string)$fname,
			'size' => (int)($_FILES['files']['size'][$id] ?? 0),
			'type' => (string)($_FILES['files']['type'][$id] ?? ''),
			'force_upload' => (bool)($force_upload_all || (isset($_POST['force_upload_' . $id]) && $_POST['force_upload_' . $id] == '1')),
		]);
    	display_notification( __FILE__ . "::" . __LINE__ . "  Processing file `$fname` with format `{$_parsers[$_POST['parser']]['name']}`" );

    	// Mantis #2708: Save uploaded file (Phase 2 refactored)
    	$bank_account_id = isset($_POST['bank_account']) ? $_POST['bank_account'] : null;
    	$file_info_array = array(
    	    'name' => $_FILES['files']['name'][$id],
    	    'type' => $_FILES['files']['type'][$id],
    	    'tmp_name' => $_FILES['files']['tmp_name'][$id],
    	    'size' => $_FILES['files']['size'][$id],
    	    'error' => $_FILES['files']['error'][$id]
    	);
    	
	    // Force upload can be applied globally or per-file (set by duplicate resolution screen)
	    $force_upload = $force_upload_all || (isset($_POST['force_upload_' . $id]) && $_POST['force_upload_' . $id] == '1');

	    // Read content BEFORE uploading (upload service may move tmp file)
	    $content = @file_get_contents($file_info_array['tmp_name']);
	    if ($content === false) {
	    	display_error(_("Failed to read uploaded file") . ': ' . $file_info_array['name']);
	    	bank_import_log_event($logger, 'file.read_failed', [
	    		'file_index' => (int)$id,
	    		'filename' => (string)$file_info_array['name'],
	    	]);
	    	$smt_err++;
	    	continue;
	    }
    	
    	try {
    	    // Create FileInfo from upload
    	    $fileInfo = FileInfo::fromUpload($file_info_array);
    	    
    	    // Upload using new service
    	    $result = $uploadService->upload(
    	        $fileInfo,
    	        $_POST['parser'],
    	        $bank_account_id,
    	        $force_upload,
    	        "Uploaded from import_statements.php"
    	    );
    	    
    	    if ($result->isSuccess()) {
    	        // New file saved or reused
    	        $file_id = $result->getFileId();
    	        $uploaded_file_ids[$id] = $file_id;
	        	bank_import_log_event($logger, 'file.upload.success', [
	        		'file_index' => (int)$id,
	        		'filename' => (string)$file_info_array['name'],
	        		'file_id' => (int)$file_id,
	        		'reused' => (bool)$result->isReused(),
	        		'forced' => (bool)$force_upload,
	        	]);
    	        
    	        if ($result->isReused()) {
    	            display_notification("Duplicate file detected! Reusing existing file ID: $file_id (saving disk space)");
    	        } elseif ($force_upload) {
    	            display_notification("File saved with ID: $file_id (forced upload, duplicate check bypassed)");
    	        } else {
    	            display_notification("File saved with ID: $file_id");
    	        }
	    	} elseif ($result->isDuplicate()) {
    	        // Duplicate detected - warn or block
    	        if ($result->allowForce()) {
	    	        // Warn mode - stage the uploaded temp file so user can choose ignore vs force-upload
	    	        display_warning($result->getMessage());

	    	        $pendingDir = BankImportPathResolver::forCurrentCompany()->pendingDir();
	    	        if (!is_dir($pendingDir)) {
	    	            @mkdir($pendingDir, 0750, true);
	    	        }

	    	        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_info_array['name']);
	    	        $stagedPath = $pendingDir . DIRECTORY_SEPARATOR
	    	            . 'PENDING_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 6)
	    	            . '_' . $sanitized;

	    	        if (!@copy($file_info_array['tmp_name'], $stagedPath)) {
	    	            display_error(_("Failed to stage duplicate file for review. Please re-upload and choose 'Upload anyway (force re-upload)'."));
	    	            $smt_err++;
	    	            continue;
	    	        }

	    	        $pending_duplicates[$id] = [
	    	            'file_index' => $id,
	    	            'filename' => $file_info_array['name'],
	    	            'size' => $file_info_array['size'],
	    	            'type' => $file_info_array['type'],
	    	            'staged_path' => $stagedPath,
	    	            'message' => $result->getMessage(),
	    	        ];

	    	        bank_import_log_event($logger, 'file.upload.duplicate_pending', [
	    	        	'file_index' => (int)$id,
	    	        	'filename' => (string)$file_info_array['name'],
	    	        	'staged_path' => (string)$stagedPath,
	    	        	'message' => (string)$result->getMessage(),
	    	        ]);

	    	        // Skip parsing/importing this file until user decides
	    	        continue;
    	        } else {
    	            // Block mode - hard reject
    	            display_error("BLOCKED: " . $result->getMessage());
	            bank_import_log_event($logger, 'file.upload.duplicate_blocked', [
	            	'file_index' => (int)$id,
	            	'filename' => (string)$file_info_array['name'],
	            	'message' => (string)$result->getMessage(),
	            ]);
    	            $has_blocked_duplicates = true;
    	            $smt_err++;
    	            continue;
    	        }
    	    } else {
    	        // Upload failed
    	        display_error("Upload failed: " . $result->getMessage());
	        bank_import_log_event($logger, 'file.upload.failed', [
	        	'file_index' => (int)$id,
	        	'filename' => (string)$file_info_array['name'],
	        	'message' => (string)$result->getMessage(),
	        ]);
    	        $smt_err++;
    	        continue;
    	    }
    	    
    	} catch (\Exception $e) {
    	    display_error("Failed to upload file '$fname': " . $e->getMessage());
    	    $smt_err++;
    	    continue;
    	}

	$bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (strncmp($content, $bom, 3) === 0) {
            $content = substr($content, 3);
        }

		bank_import_log_event($logger, 'file.parse.started', [
			'file_index' => (int)$id,
			'filename' => (string)$fname,
		]);
		try {
			$statements = $parser->parse($content, $static_data, $debug=false); // false for no debug, true for debug
		} catch (\Throwable $e) {
			bank_import_log_event($logger, 'file.parse.error', [
				'file_index' => (int)$id,
				'filename' => (string)$fname,
				'error' => $e->getMessage(),
			]);
			display_error(_("Failed to parse uploaded file") . ': ' . $fname . ' (' . $e->getMessage() . ')');
			$smt_err++;
			continue;
		}
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
	bank_import_log_event($logger, 'file.parse.completed', [
		'file_index' => (int)$id,
		'filename' => (string)$fname,
		'statement_count' => is_array($statements) ? count($statements) : 0,
		'valid_statements_total_so_far' => (int)$smt_ok,
		'invalid_statements_total_so_far' => (int)$smt_err,
		'transactions_total_so_far' => (int)$trz_ok,
		'statement_ids' => array_values(array_map(function ($s) { return $s->statementId ?? null; }, is_array($statements) ? $statements : [])),
	]);

    	echo "======================================\n";
    	echo "Valid statements   : $smt_ok\n";
    	echo "Invalid statements : $smt_err\n";
    	echo "Total transactions : $trz_ok\n";
	// Keep key alignment with uploaded_file_ids for later linking
	$multistatements[$id] = $statements;
    }
    echo "</pre></td>";
    end_row();

	// If there are duplicates requiring user decision, store partial results and render a review screen
	if (!empty($pending_duplicates) && !$force_upload_all) {
		$_SESSION['bank_import_pending'] = [
			'parser' => $_POST['parser'],
			'bank_account' => isset($_POST['bank_account']) ? $_POST['bank_account'] : null,
			'multistatements' => serialize($multistatements),
			'uploaded_file_ids' => $uploaded_file_ids,
			'uploaded_filenames' => $uploaded_filenames,
			'duplicates' => $pending_duplicates,
			'log_path' => isset($_SESSION['bank_import_run_log_path']) ? $_SESSION['bank_import_run_log_path'] : null,
		];
		bank_import_log_event($logger, 'duplicate.review.required', [
			'count' => count($pending_duplicates),
		]);

		start_row();
		echo '<td>';
		echo '<div style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 10px 0;">';
		echo '<h3 style="color: #856404; margin-top: 0;">Duplicate Files Detected</h3>';
		echo '<p>' . _("Select what to do for each duplicate, then proceed.") . '</p>';
		echo '<form method="post">';

		foreach ($pending_duplicates as $dup) {
			$idx = (int)$dup['file_index'];
			echo '<div style="margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #ffc107;">';
			echo '<strong>' . htmlspecialchars($dup['filename']) . '</strong><br>';
			echo 'Size: ' . number_format($dup['size'] / 1024, 2) . ' KB<br>';
			echo '<div style="margin-top: 6px;">' . htmlspecialchars($dup['message']) . '</div>';
			echo '<div style="margin-top: 8px;">';
			echo '<label style="margin-right: 15px;">'
				. '<input type="radio" name="dup_action[' . $idx . ']" value="ignore" checked> '
				. _("Ignore")
				. '</label>';
			echo '<label>'
				. '<input type="radio" name="dup_action[' . $idx . ']" value="force"> '
				. _("Upload again anyway")
				. '</label>';
			echo '</div>';
			echo '</div>';
		}

		echo '<button type="submit" name="resolve_duplicates" value="1" style="background-color: #0d6efd; color: white; padding: 10px 20px; border: none; cursor: pointer; margin-right: 10px;">'
			. _("Proceed")
			. '</button>';
		echo '<button type="submit" name="cancel_duplicates" value="1" style="background-color: #6c757d; color: white; padding: 10px 20px; border: none; cursor: pointer;">'
			. _("Cancel")
			. '</button>';
		echo '</form>';
		echo '</div>';
		echo '</td>';
		end_row();
		end_table(1);
		return;
	}

	// Bank Account Resolution step (detected account numbers \u2192 FA bank accounts)
	if (maybe_render_account_resolution_screen($_POST['parser'], isset($_POST['bank_account']) ? $_POST['bank_account'] : null, $multistatements, $uploaded_file_ids, $uploaded_filenames)) {
		end_table(1);
		return;
	}
	// bi_bank_accounts mappings are persisted during account resolution.
    
    start_row();
    echo '<td>';
	submit_center_first('goback', 'Go back');
	if ($smt_err == 0 && !$has_blocked_duplicates)
	    submit_center_last('import', 'Import');

    echo '</td>';
    end_row();
    
    end_table(1);
    hidden('parser', $_POST['parser']);
    if ($smt_err == 0) {
	$_SESSION['statements'] = serialize($statements);
	$_SESSION['multistatements'] = serialize($multistatements);
	// Mantis #2708: Store uploaded file IDs for linking to statements
	$_SESSION['uploaded_file_ids'] = $uploaded_file_ids;
	$_SESSION['uploaded_filenames'] = $uploaded_filenames;
	bank_import_log_event($logger, 'run.upload_parse.completed', [
		'valid_statements' => (int)$smt_ok,
		'invalid_statements' => (int)$smt_err,
		'total_transactions' => (int)$trz_ok,
		'blocked_duplicates' => (bool)$has_blocked_duplicates,
	]);
    }
}

/**
 * Determine if a bank account number exists in FA.
 *
 * @param string $bankAccountNumber
 * @return bool
 */
function fa_bank_account_number_exists(string $bankAccountNumber): bool
{
	$bankAccountNumber = trim($bankAccountNumber);
	if ($bankAccountNumber === '') {
		return false;
	}
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	return bi_bank_accounts_model::fa_get_bank_account_id_by_number($bankAccountNumber) !== null;
}

/**
 * Resolve FA bank account id by bank_account_number.
 */
function fa_get_bank_account_id_by_number(string $bankAccountNumber): ?int
{
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	return bi_bank_accounts_model::fa_get_bank_account_id_by_number($bankAccountNumber);
}

function bi_bank_accounts_table_exists(): bool
{
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	return bi_bank_accounts_model::table_exists();
}

function bi_bank_accounts_get_row(int $bankAccountId): ?array
{
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	return bi_bank_accounts_model::get_row((int)$bankAccountId);
}

function bi_bank_accounts_upsert(int $bankAccountId, array $meta): void
{
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	bi_bank_accounts_model::upsert((int)$bankAccountId, $meta);
}

/**
 * Build desired bi_bank_accounts values from parsed statements.
 *
 * @return array<int,array{acctid:string,bankid:string,intu_bid:string,curdef:string,accttype:string,detected_acctid:string,bank_account_number:string}>
 */
function collect_desired_bi_bank_accounts_rows(array $multistatements): array
{
	$desired = [];
	foreach ($multistatements as $fileIndex => $statements) {
		if (!is_array($statements)) {
			continue;
		}
		foreach ($statements as $smt) {
			if (!is_object($smt)) {
				continue;
			}
			$detectedAcctid = isset($smt->acctid) ? trim((string)$smt->acctid) : '';
			$faAccountNumber = isset($smt->account) ? trim((string)$smt->account) : '';
			if ($detectedAcctid === '' || $faAccountNumber === '') {
				continue;
			}
			$bankAccountId = fa_get_bank_account_id_by_number($faAccountNumber);
			if ($bankAccountId === null) {
				continue;
			}
			if (isset($desired[$bankAccountId])) {
				// If multiple statements map to the same FA bank account, keep the first.
				continue;
			}
			$desired[$bankAccountId] = [
				'acctid' => $detectedAcctid,
				'bankid' => isset($smt->bankid) ? trim((string)$smt->bankid) : '',
				'intu_bid' => isset($smt->intu_bid) ? trim((string)$smt->intu_bid) : '',
				'curdef' => isset($smt->currency) ? trim((string)$smt->currency) : '',
				'accttype' => '',
				'detected_acctid' => $detectedAcctid,
				'bank_account_number' => $faAccountNumber,
			];
		}
	}
	return $desired;
}

function bi_bank_accounts_meta_differs(array $existing, array $desired): bool
{
	$fields = ['acctid', 'bankid', 'intu_bid', 'curdef'];
	foreach ($fields as $f) {
		$ex = isset($existing[$f]) ? trim((string)$existing[$f]) : '';
		$de = isset($desired[$f]) ? trim((string)$desired[$f]) : '';
		if ($de === '') {
			continue;
		}
		if ($ex !== $de) {
			return true;
		}
	}
	return false;
}

/**
 * Prompt user to add/update bi_bank_accounts mapping based on file metadata.
 * Returns true if it rendered a blocking screen and caller should return.
 */
function maybe_render_bi_bank_accounts_confirmation_screen($parserType, $bankAccountId, array $multistatements, array $uploaded_file_ids, array $uploaded_filenames): bool
{
	$logger = bank_import_get_logger();
	if (!bi_bank_accounts_table_exists()) {
		return false;
	}

	$desired = collect_desired_bi_bank_accounts_rows($multistatements);
	if (empty($desired)) {
		return false;
	}

	$pending = [];
	foreach ($desired as $faId => $meta) {
		$existing = bi_bank_accounts_get_row((int)$faId);
		if ($existing === null || empty($existing)) {
			$pending[(int)$faId] = ['existing' => null, 'desired' => $meta, 'mode' => 'add'];
			continue;
		}
		if (bi_bank_accounts_meta_differs($existing, $meta)) {
			$pending[(int)$faId] = ['existing' => $existing, 'desired' => $meta, 'mode' => 'update'];
		}
	}

	if (empty($pending)) {
		return false;
	}

	$_SESSION['bank_import_bi_bank_accounts_confirm'] = [
		'parser' => $parserType,
		'bank_account' => $bankAccountId,
		'multistatements' => serialize($multistatements),
		'uploaded_file_ids' => $uploaded_file_ids,
		'uploaded_filenames' => $uploaded_filenames,
		'pending' => $pending,
		'log_path' => isset($_SESSION['bank_import_run_log_path']) ? $_SESSION['bank_import_run_log_path'] : null,
	];

	bank_import_log_event($logger, 'bi_bank_accounts.confirm.required', [
		'count' => count($pending),
		'bank_account_ids' => array_keys($pending),
	]);

	echo '<tr><td>';
	echo '<div style="background-color:#e7f3ff;border:1px solid #0d6efd;padding:15px;margin:10px 0;">';
	echo '<h3 style="color:#0d6efd;margin-top:0;">' . _("Confirm Bank Account Mapping") . '</h3>';
	echo '<p>' . _("The imported file contains bank metadata (OFX/QFX ACCTID/BANKID/BID). Do you want to save/update this mapping for the selected FrontAccounting bank account(s)?") . '</p>';
	echo '<form method="post">';
	echo '<input type="hidden" name="parser" value="' . htmlspecialchars((string)$parserType) . '">';
	if ($bankAccountId !== null) {
		echo '<input type="hidden" name="bank_account" value="' . htmlspecialchars((string)$bankAccountId) . '">';
	}

	echo '<table class="tablestyle" style="width:100%;">';
	echo '<tr><th>' . _("FA Bank Account") . '</th><th>' . _("Detected (from file)") . '</th><th>' . _("Existing (saved)") . '</th><th>' . _("Action") . '</th></tr>';
	foreach ($pending as $faId => $row) {
		$desiredMeta = $row['desired'];
		$existingMeta = $row['existing'];
		$ba = get_bank_account((int)$faId);
		$baLabel = is_array($ba)
			? (trim((string)($ba['bank_account_name'] ?? '')) . ' (' . trim((string)($ba['bank_account_number'] ?? $desiredMeta['bank_account_number'])) . ')')
			: ('#' . (int)$faId . ' (' . $desiredMeta['bank_account_number'] . ')');

		$detectedLabel = 'ACCTID=' . ($desiredMeta['acctid'] !== '' ? $desiredMeta['acctid'] : '-')
			. ' | BANKID=' . ($desiredMeta['bankid'] !== '' ? $desiredMeta['bankid'] : '-')
			. ' | BID=' . ($desiredMeta['intu_bid'] !== '' ? $desiredMeta['intu_bid'] : '-')
			. ' | CUR=' . ($desiredMeta['curdef'] !== '' ? $desiredMeta['curdef'] : '-');

		$existingLabel = '-';
		if (is_array($existingMeta)) {
			$existingLabel = 'ACCTID=' . (trim((string)($existingMeta['acctid'] ?? '')) !== '' ? trim((string)($existingMeta['acctid'] ?? '')) : '-')
				. ' | BANKID=' . (trim((string)($existingMeta['bankid'] ?? '')) !== '' ? trim((string)($existingMeta['bankid'] ?? '')) : '-')
				. ' | BID=' . (trim((string)($existingMeta['intu_bid'] ?? '')) !== '' ? trim((string)($existingMeta['intu_bid'] ?? '')) : '-')
				. ' | CUR=' . (trim((string)($existingMeta['curdef'] ?? '')) !== '' ? trim((string)($existingMeta['curdef'] ?? '')) : '-');
		}

		$defaultAction = 'update';
		echo '<tr>';
		echo '<td>' . htmlspecialchars($baLabel) . '</td>';
		echo '<td><code>' . htmlspecialchars($detectedLabel) . '</code></td>';
		echo '<td><code>' . htmlspecialchars($existingLabel) . '</code></td>';
		echo '<td>';
		echo '<label style="margin-right:12px;">'
			. '<input type="radio" name="bi_action[' . (int)$faId . ']" value="keep"> '
			. _("Keep existing")
			. '</label>';
		echo '<label>'
			. '<input type="radio" name="bi_action[' . (int)$faId . ']" value="update" checked> '
			. ($row['mode'] === 'add' ? _("Add mapping") : _("Update mapping"))
			. '</label>';
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';

	echo '<div style="margin-top:12px;">';
	echo '<button type="submit" name="confirm_bi_bank_accounts" value="1" style="background-color:#0d6efd;color:white;padding:10px 20px;border:none;cursor:pointer;margin-right:10px;">'
		. _("Continue")
		. '</button>';
	echo '<button type="submit" name="skip_bi_bank_accounts_confirm" value="1" style="background-color:#6c757d;color:white;padding:10px 20px;border:none;cursor:pointer;">'
		. _("Skip")
		. '</button>';
	echo '</div>';

	echo '</form>';
	echo '</div>';
	echo '</td></tr>';

	return true;
}

function confirm_bi_bank_accounts_mappings(): void
{
	// Deprecated: bi_bank_accounts mappings are now persisted during account resolution.
	unset($_SESSION['bank_import_bi_bank_accounts_confirm']);
	return;
}

function skip_bi_bank_accounts_confirmation(): void
{
	if (empty($_SESSION['bank_import_bi_bank_accounts_confirm'])) {
		return;
	}
	$pending = $_SESSION['bank_import_bi_bank_accounts_confirm'];
	$parserType = $pending['parser'];
	$bankAccountId = $pending['bank_account'];
	$multistatements = !empty($pending['multistatements']) ? unserialize($pending['multistatements']) : [];
	$uploaded_file_ids = !empty($pending['uploaded_file_ids']) ? $pending['uploaded_file_ids'] : [];
	$uploaded_filenames = !empty($pending['uploaded_filenames']) ? $pending['uploaded_filenames'] : [];

	start_table(TABLESTYLE);
	start_row();
	echo "<td width=100%><pre>\n";
	echo "Skipped saving bank account mappings.\n";
	echo "</pre></td>";
	end_row();
	start_row();
	echo '<td>';
	submit_center_first('goback', 'Go back');
	submit_center_last('import', 'Import');
	echo '</td>';
	end_row();
	end_table(1);
	hidden('parser', $parserType);
	if ($bankAccountId !== null) {
		hidden('bank_account', $bankAccountId);
	}

	$_SESSION['multistatements'] = serialize($multistatements);
	$_SESSION['uploaded_file_ids'] = $uploaded_file_ids;
	$_SESSION['uploaded_filenames'] = $uploaded_filenames;

	unset($_SESSION['bank_import_bi_bank_accounts_confirm']);
}

/**
 * Resolve detected OFX ACCTID to a FA bank_account_number using the module-owned
 * bi_bank_accounts xref table (if present).
 *
 * @param array<int,string> $detectedAccounts
 * @return array<string,string> map detectedAcctid => FA bank_account_number
 */
function resolve_detected_accounts_via_bi_bank_accounts(array $detectedAccounts): array
{
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	return bi_bank_accounts_model::resolve_detected_accounts_to_bank_account_numbers($detectedAccounts);
}

/**
 * Collect detected identity metadata for each detected acctid.
 *
 * @return array<string,array{acctid:string,bankid:string,intu_bid:string,curdef:string,accttype:string}>
 */
function collect_detected_identity_meta(array $multistatements): array
{
	$metaByDetected = [];
	foreach ($multistatements as $statements) {
		if (!is_array($statements)) {
			continue;
		}
		foreach ($statements as $smt) {
			if (!is_object($smt)) {
				continue;
			}
			$detectedAcctid = isset($smt->acctid) ? trim((string)$smt->acctid) : '';
			if ($detectedAcctid === '') {
				continue;
			}
			if (!isset($metaByDetected[$detectedAcctid])) {
				$metaByDetected[$detectedAcctid] = [
					'acctid' => $detectedAcctid,
					'bankid' => isset($smt->bankid) ? trim((string)$smt->bankid) : '',
					'intu_bid' => isset($smt->intu_bid) ? trim((string)$smt->intu_bid) : '',
					'curdef' => isset($smt->currency) ? trim((string)$smt->currency) : '',
					'accttype' => '',
				];
				continue;
			}
			if ($metaByDetected[$detectedAcctid]['bankid'] === '' && isset($smt->bankid)) {
				$metaByDetected[$detectedAcctid]['bankid'] = trim((string)$smt->bankid);
			}
			if ($metaByDetected[$detectedAcctid]['intu_bid'] === '' && isset($smt->intu_bid)) {
				$metaByDetected[$detectedAcctid]['intu_bid'] = trim((string)$smt->intu_bid);
			}
			if ($metaByDetected[$detectedAcctid]['curdef'] === '' && isset($smt->currency)) {
				$metaByDetected[$detectedAcctid]['curdef'] = trim((string)$smt->currency);
			}
		}
	}
	return $metaByDetected;
}

/**
 * Apply any saved associations (detected acctid \u2192 FA bank account id) to parsed statements.
 * Returns a map of detectedAcct => resolved FA bank_account_number.
 *
 * @param array $multistatements
 * @return array<string,string>
 */
function load_saved_account_associations(array $multistatements): array
{
	$service = new StatementAccountMappingService();
	$repo = new DatabaseConfigRepository();
	$detectedByFile = $service->collectDetectedAccountsByFile($multistatements);

	$detectedAll = [];
	foreach ($detectedByFile as $list) {
		foreach ($list as $detected) {
			$detectedAll[$detected] = true;
		}
	}

	$resolved = [];
	foreach (array_keys($detectedAll) as $detected) {
		$key = DetectedAccountAssociationKey::forDetectedAccount($detected);
		$bankAccountId = $repo->get($key);
		if ($bankAccountId === null || $bankAccountId === '') {
			continue;
		}
		$bankAccountId = (int)$bankAccountId;
		if ($bankAccountId <= 0) {
			continue;
		}
		$ba = get_bank_account($bankAccountId);
		if (is_array($ba) && !empty($ba['bank_account_number'])) {
			$resolved[$detected] = $ba['bank_account_number'];
		}
	}

	return $resolved;
}

/**
 * Render the account-resolution UI if needed.
 *
 * Returns true if it rendered a blocking screen and caller should return early.
 */
function maybe_render_account_resolution_screen($parserType, $bankAccountId, array &$multistatements, array $uploaded_file_ids, array $uploaded_filenames): bool
{
	$mappingService = new StatementAccountMappingService();
	$logger = bank_import_get_logger();

	// Auto-apply any previously-saved associations.
	$saved = load_saved_account_associations($multistatements);
	$resolvedMap = $saved;
	if (!empty($resolvedMap)) {
		$multistatements = $mappingService->applyAccountNumberMapping($multistatements, $resolvedMap);
	}

	// Collect unresolved detected accounts (unique), grouped by file index.
	$detectedByFile = $mappingService->collectDetectedAccountsByFile($multistatements);
	$detectedAll = [];
	foreach ($detectedByFile as $detectedList) {
		foreach ($detectedList as $detected) {
			$detected = trim((string)$detected);
			if ($detected !== '') {
				$detectedAll[$detected] = true;
			}
		}
	}

	// Attempt automatic resolution using module-owned bi_bank_accounts (legacy PROD stored this on bank_accounts).
	$auto = resolve_detected_accounts_via_bi_bank_accounts(array_keys($detectedAll));
	foreach ($auto as $detected => $bankAccountNumber) {
		if (!isset($resolvedMap[$detected]) && fa_bank_account_number_exists($bankAccountNumber)) {
			$resolvedMap[$detected] = $bankAccountNumber;
		}
	}
	if (!empty($resolvedMap)) {
		$multistatements = $mappingService->applyAccountNumberMapping($multistatements, $resolvedMap);
	}

	$unresolved = [];
	foreach ($detectedByFile as $fileIndex => $detectedList) {
		foreach ($detectedList as $detected) {
			// If statement->account was already mapped to a valid FA bank account number, we're good.
			// Otherwise we require user resolution for this detected account.
			if (isset($resolvedMap[$detected]) && fa_bank_account_number_exists($resolvedMap[$detected])) {
				continue;
			}
			if (fa_bank_account_number_exists($detected)) {
				continue;
			}
			if (!isset($unresolved[$detected])) {
				$unresolved[$detected] = [];
			}
			$unresolved[$detected][(int)$fileIndex] = true;
		}
	}

	if (empty($unresolved)) {
		return false;
	}

	// Persist pending state and render UI
	$_SESSION['bank_import_account_resolution'] = [
		'parser' => $parserType,
		'bank_account' => $bankAccountId,
		'multistatements' => serialize($multistatements),
		'uploaded_file_ids' => $uploaded_file_ids,
		'uploaded_filenames' => $uploaded_filenames,
		'unresolved' => array_map(function ($files) {
			return array_keys($files);
		}, $unresolved),
		'log_path' => isset($_SESSION['bank_import_run_log_path']) ? $_SESSION['bank_import_run_log_path'] : null,
	];

	bank_import_log_event($logger, 'account_resolution.required', [
		'unresolved_count' => count($unresolved),
		'detected_accounts' => array_keys($unresolved),
	]);

	echo '<tr><td>';
	echo '<div style="background-color:#fff3cd;border:1px solid #ffc107;padding:15px;margin:10px 0;">';
	echo '<h3 style="color:#856404;margin-top:0;">' . _("Bank Account Resolution Required") . '</h3>';
	echo '<p>' . _("Some files contain detected account numbers that don't match any FrontAccounting bank account. Please choose which FA bank account to use.") . '</p>';
	
	echo '<form method="post">';
	// carry forward minimal context
	echo '<input type="hidden" name="parser" value="' . htmlspecialchars((string)$parserType) . '">';
	if ($bankAccountId !== null) {
		echo '<input type="hidden" name="bank_account" value="' . htmlspecialchars((string)$bankAccountId) . '">';
	}

	echo '<table class="tablestyle" style="width:100%;">';
	echo '<tr><th>' . _("File(s)") . '</th><th>' . _("Detected Account") . '</th><th>' . _("Use FA Bank Account") . '</th><th>' . _("Remember") . '</th></tr>';

	foreach ($unresolved as $detected => $fileMap) {
		$detKey = substr(sha1($detected), 0, 12);
		$fileNames = [];
		foreach (array_keys($fileMap) as $fileIndex) {
			$fileNames[] = $uploaded_filenames[$fileIndex] ?? ('#' . $fileIndex);
		}
		$fileLabel = htmlspecialchars(implode(', ', $fileNames));

		echo '<tr>';
		echo '<td>' . $fileLabel . '</td>';
		echo '<td><code>' . htmlspecialchars($detected) . '</code></td>';
		echo '<td>';
		// bank_accounts_list(name, selected_id, submit_on_change, spec_option)
		echo bank_accounts_list('resolved_bank_account[' . $detKey . ']', null, false, false);
		echo '</td>';
		echo '<td style="text-align:center;">'
			. '<input type="checkbox" name="remember_mapping[' . $detKey . ']" value="1" checked>'
			. '</td>';
		echo '</tr>';
		// include original detected value for this row
		echo '<input type="hidden" name="detected_account[' . $detKey . ']" value="' . htmlspecialchars($detected) . '">';
	}

	echo '</table>';

	echo '<div style="margin-top:12px;">';
	echo '<button type="submit" name="resolve_accounts" value="1" style="background-color:#0d6efd;color:white;padding:10px 20px;border:none;cursor:pointer;margin-right:10px;">'
		. _("Proceed")
		. '</button>';
	echo '<button type="submit" name="cancel_account_resolution" value="1" style="background-color:#6c757d;color:white;padding:10px 20px;border:none;cursor:pointer;">'
		. _("Cancel")
		. '</button>';
	echo '</div>';
	
	echo '</form>';
	echo '</div>';
	echo '</td></tr>';

	return true;
}

function resolve_account_mappings() {
	start_table(TABLESTYLE);
	start_row();
	echo "<td width=100%><pre>\n";

	if (empty($_SESSION['bank_import_account_resolution'])) {
		display_error(_("No pending account resolution session found. Please upload the file(s) again."));
		echo "</pre></td>";
		end_row();
		end_table(1);
		return;
	}

	$pending = $_SESSION['bank_import_account_resolution'];
	if (!empty($pending['log_path']) && empty($_SESSION['bank_import_run_log_path'])) {
		$_SESSION['bank_import_run_log_path'] = $pending['log_path'];
	}
	$logger = bank_import_get_logger();
	$parserType = $pending['parser'];
	$bankAccountId = $pending['bank_account'];
	$multistatements = !empty($pending['multistatements']) ? unserialize($pending['multistatements']) : [];
	$uploaded_file_ids = !empty($pending['uploaded_file_ids']) ? $pending['uploaded_file_ids'] : [];
	$uploaded_filenames = !empty($pending['uploaded_filenames']) ? $pending['uploaded_filenames'] : [];
	
	$detected_account = isset($_POST['detected_account']) && is_array($_POST['detected_account']) ? $_POST['detected_account'] : [];
	$resolved_bank_account = isset($_POST['resolved_bank_account']) && is_array($_POST['resolved_bank_account']) ? $_POST['resolved_bank_account'] : [];
	$remember_mapping = isset($_POST['remember_mapping']) && is_array($_POST['remember_mapping']) ? $_POST['remember_mapping'] : [];

	$detectedToAccountNumber = [];
	$repo = new DatabaseConfigRepository();
	$rememberedCount = 0;
	$metaByDetected = collect_detected_identity_meta($multistatements);

	foreach ($detected_account as $detKey => $detected) {
		$detected = (string)$detected;
		$selectedId = isset($resolved_bank_account[$detKey]) ? (int)$resolved_bank_account[$detKey] : 0;
		if ($selectedId <= 0) {
			display_error(_("Please select a FrontAccounting bank account for detected account") . ': ' . htmlspecialchars($detected));
			continue;
		}
		$ba = get_bank_account($selectedId);
		if (!is_array($ba) || empty($ba['bank_account_number'])) {
			display_error(_("Invalid bank account selection for detected account") . ': ' . htmlspecialchars($detected));
			continue;
		}
		$detectedToAccountNumber[$detected] = $ba['bank_account_number'];

		if (!empty($remember_mapping[$detKey])) {
			if (isset($metaByDetected[$detected])) {
				bi_bank_accounts_upsert((int)$selectedId, $metaByDetected[$detected]);
			}

			$key = DetectedAccountAssociationKey::forDetectedAccount($detected);
			$username = isset($_SESSION['wa_current_user']) && isset($_SESSION['wa_current_user']->name)
				? $_SESSION['wa_current_user']->name
				: null;
			$repo->set($key, (string)$selectedId, $username, 'Associate detected account to FA bank account');
			$rememberedCount++;
		}
	}

	// If any errors were emitted above, stop here and re-render the form.
	if (empty($detectedToAccountNumber)) {
		echo "</pre></td>";
		end_row();
		end_table(1);
		return;
	}

	$mappingService = new StatementAccountMappingService();
	$multistatements = $mappingService->applyAccountNumberMapping($multistatements, $detectedToAccountNumber);
	bank_import_log_event($logger, 'account_resolution.applied', [
		'mapping_count' => count($detectedToAccountNumber),
		'remembered_count' => (int)$rememberedCount,
		'mappings' => $detectedToAccountNumber,
	]);

	echo "Resolved detected accounts successfully.\n";
	echo "</pre></td>";
	end_row();

	// bi_bank_accounts mappings are persisted during account resolution.
	start_row();
	echo '<td>';
	submit_center_first('goback', 'Go back');
	submit_center_last('import', 'Import');
	echo '</td>';
	end_row();
	end_table(1);
	hidden('parser', $parserType);
	if ($bankAccountId !== null) {
		hidden('bank_account', $bankAccountId);
	}

	$_SESSION['multistatements'] = serialize($multistatements);
	$_SESSION['uploaded_file_ids'] = $uploaded_file_ids;
	$_SESSION['uploaded_filenames'] = $uploaded_filenames;

	unset($_SESSION['bank_import_account_resolution']);
}

function cancel_account_resolution() {
	unset($_SESSION['bank_import_account_resolution']);
}

function resolve_duplicate_uploads() {
	start_table(TABLESTYLE);
	start_row();
	echo "<td width=100%><pre>\n";

	if (empty($_SESSION['bank_import_pending'])) {
		display_error(_("No pending duplicate upload session found. Please upload the file(s) again."));
		echo "</pre></td>";
		end_row();
		end_table(1);
		return;
	}

	$pending = $_SESSION['bank_import_pending'];
	if (!empty($pending['log_path']) && empty($_SESSION['bank_import_run_log_path'])) {
		$_SESSION['bank_import_run_log_path'] = $pending['log_path'];
	}
	$logger = bank_import_get_logger();
	$parserType = $pending['parser'];
	$bank_account_id = $pending['bank_account'];
	$multistatements = !empty($pending['multistatements']) ? unserialize($pending['multistatements']) : [];
	$uploaded_file_ids = !empty($pending['uploaded_file_ids']) ? $pending['uploaded_file_ids'] : [];
	$uploaded_filenames = !empty($pending['uploaded_filenames']) ? $pending['uploaded_filenames'] : [];
	$duplicates = !empty($pending['duplicates']) ? $pending['duplicates'] : [];
	$actions = isset($_POST['dup_action']) && is_array($_POST['dup_action']) ? $_POST['dup_action'] : [];

	// init services
	$uploadService = FileUploadService::create();
	$parserClass = $parserType . '_parser';
	$parser = new $parserClass;

	// static parser data
	$static_data = array();
	$_parsers = getParsers();
	if ($bank_account_id !== null && isset($_parsers[$parserType]['select']['bank_account'])) {
		$bank_account = get_bank_account($bank_account_id);
		$static_data['account'] = $bank_account['bank_account_number'];
		$static_data['account_number'] = $bank_account['bank_account_number'];
		$static_data['currency'] = $bank_account['bank_curr_code'];
		$static_data['account_code'] = $bank_account['account_code'];
		$static_data['account_type'] = $bank_account['account_type'];
		$static_data['account_name'] = $bank_account['bank_account_name'];
		$static_data['bank_charge_act'] = $bank_account['bank_charge_act'];
	}

	$smt_ok = 0;
	$trz_ok = 0;
	$smt_err = 0;

	foreach ($duplicates as $dup) {
		$idx = (int)$dup['file_index'];
		$action = isset($actions[$idx]) ? $actions[$idx] : 'ignore';

		if ($action !== 'force') {
			bank_import_log_event($logger, 'duplicate.review.ignored', [
				'file_index' => (int)$idx,
				'filename' => (string)($dup['filename'] ?? ''),
			]);
			if (!empty($dup['staged_path']) && file_exists($dup['staged_path'])) {
				@unlink($dup['staged_path']);
			}
			display_notification(_("Ignoring duplicate file") . ': ' . $dup['filename']);
			continue;
		}

		if (empty($dup['staged_path']) || !file_exists($dup['staged_path'])) {
			display_error(_("Staged file not found for") . ' ' . $dup['filename'] . _(". Please upload again."));
			$smt_err++;
			continue;
		}

		try {
			// Read content BEFORE upload (upload service will move/rename the staged file)
			$content = @file_get_contents($dup['staged_path']);
			if ($content === false) {
				display_error(_("Failed to read staged file for") . ' ' . $dup['filename'] . _(". Please upload again."));
				bank_import_log_event($logger, 'duplicate.review.force_read_failed', [
					'file_index' => (int)$idx,
					'filename' => (string)($dup['filename'] ?? ''),
					'staged_path' => (string)($dup['staged_path'] ?? ''),
				]);
				$smt_err++;
				continue;
			}

			$fileInfo = new FileInfo(
				$dup['filename'],
				$dup['staged_path'],
				filesize($dup['staged_path']),
				$dup['type'] ?: 'application/octet-stream'
			);

			$result = $uploadService->upload(
				$fileInfo,
				$parserType,
				$bank_account_id,
				true,
				"Forced upload after duplicate review"
			);

			if (!$result->isSuccess()) {
				display_error(_("Force upload failed") . ': ' . $result->getMessage());
				bank_import_log_event($logger, 'duplicate.review.force_upload_failed', [
					'file_index' => (int)$idx,
					'filename' => (string)($dup['filename'] ?? ''),
					'message' => (string)$result->getMessage(),
				]);
				$smt_err++;
				continue;
			}

			$uploaded_file_ids[$idx] = $result->getFileId();
			bank_import_log_event($logger, 'duplicate.review.force_uploaded', [
				'file_index' => (int)$idx,
				'filename' => (string)($dup['filename'] ?? ''),
				'file_id' => (int)$result->getFileId(),
			]);
			display_notification(_("File saved with ID") . ': ' . $result->getFileId());

			$bom = pack('CCC', 0xEF, 0xBB, 0xBF);
			if (strncmp($content, $bom, 3) === 0) {
				$content = substr($content, 3);
			}

			bank_import_log_event($logger, 'file.parse.started', [
				'file_index' => (int)$idx,
				'filename' => (string)($dup['filename'] ?? ''),
				'forced_duplicate_upload' => true,
			]);
			$statements = $parser->parse($content, $static_data, $debug=false);
			foreach ($statements as $smt) {
				echo "statement: {$smt->statementId}:";
				if ($smt->validate($debug = false)) {
					$smt_ok++;
					$trz_cnt = count($smt->transactions);
					$trz_ok += $trz_cnt;
					echo " is valid, $trz_cnt transactions\n";
				} else {
					echo " is invalid!!!!!!!!!\n";
					$smt->validate($debug=true);
					$smt_err++;
				}
			}

			$multistatements[$idx] = $statements;
			$uploaded_filenames[$idx] = $dup['filename'];
			bank_import_log_event($logger, 'file.parse.completed', [
				'file_index' => (int)$idx,
				'filename' => (string)($dup['filename'] ?? ''),
				'statement_count' => is_array($statements) ? count($statements) : 0,
			]);
		} catch (\Exception $e) {
			display_error(_("Failed to force upload") . ' ' . $dup['filename'] . ': ' . $e->getMessage());
			bank_import_log_event($logger, 'duplicate.review.force_exception', [
				'file_index' => (int)$idx,
				'filename' => (string)($dup['filename'] ?? ''),
				'error' => $e->getMessage(),
			]);
			$smt_err++;
			continue;
		}
	}

	echo "======================================\n";
	echo "Valid statements   : $smt_ok\n";
	echo "Invalid statements : $smt_err\n";
	echo "Total transactions : $trz_ok\n";

	echo "</pre></td>";
	end_row();

	// Bank Account Resolution step
	if ($smt_err == 0) {
		if (maybe_render_account_resolution_screen($parserType, $bank_account_id, $multistatements, $uploaded_file_ids, $uploaded_filenames)) {
			end_table(1);
			unset($_SESSION['bank_import_pending']);
			return;
		}
	}

	start_row();
	echo '<td>';
	submit_center_first('goback', 'Go back');
	if ($smt_err == 0)
		submit_center_last('import', 'Import');
	echo '</td>';
	end_row();
	end_table(1);
	hidden('parser', $parserType);
	if ($bank_account_id !== null) {
		hidden('bank_account', $bank_account_id);
	}

	if ($smt_err == 0) {
		$_SESSION['multistatements'] = serialize($multistatements);
		$_SESSION['uploaded_file_ids'] = $uploaded_file_ids;
		$_SESSION['uploaded_filenames'] = $uploaded_filenames;
	}

	unset($_SESSION['bank_import_pending']);
}

function cancel_duplicate_uploads() {
	if (empty($_SESSION['bank_import_pending'])) {
		return;
	}
	$pending = $_SESSION['bank_import_pending'];
	$duplicates = !empty($pending['duplicates']) ? $pending['duplicates'] : [];
	foreach ($duplicates as $dup) {
		if (!empty($dup['staged_path']) && file_exists($dup['staged_path'])) {
			@unlink($dup['staged_path']);
		}
	}
	unset($_SESSION['bank_import_pending']);
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
if (!empty($_POST['upload'])) {
	if (isset($_FILES['files']) && isset($_FILES['files']['error']) && is_array($_FILES['files']['error']) && $_FILES['files']['error'][0] == 0) {
		parse_uploaded_files();
	} else {
		display_error(_("No files were uploaded. Please choose at least one file."));
		do_upload_form();
	}
}

//if user is resolving duplicates, force upload selected duplicates and parse them
if (!empty($_POST['resolve_duplicates'])) {
	resolve_duplicate_uploads();
}

//if user cancels duplicate resolution
if (!empty($_POST['cancel_duplicates'])) {
	cancel_duplicate_uploads();
	do_upload_form();
}

// if user is resolving bank account mappings
if (!empty($_POST['resolve_accounts'])) {
	resolve_account_mappings();
}

// if user is confirming bi_bank_accounts updates
if (!empty($_POST['confirm_bi_bank_accounts'])) {
	confirm_bi_bank_accounts_mappings();
}

// if user skips bi_bank_accounts updates
if (!empty($_POST['skip_bi_bank_accounts_confirm'])) {
	skip_bi_bank_accounts_confirmation();
}

// if user cancels bank account resolution
if (!empty($_POST['cancel_account_resolution'])) {
	cancel_account_resolution();
	do_upload_form();
}

//if import is hit, perform the import
if (@$_POST['import']) {
    import_statements();
}


end_form(2);

end_page();
?>
