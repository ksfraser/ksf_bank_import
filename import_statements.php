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

// Mantis #2708: File upload management - Refactored (Phase 2)
require_once __DIR__ . '/vendor/autoload.php';
use Ksfraser\FaBankImport\Service\FileUploadService;
use Ksfraser\FaBankImport\ValueObject\FileInfo;

//TODO Migrate to use HTML classes

page(_($help_context = "Import Bank Statement"));

        include_once "views/module_menu_view.php"; // Include the ModuleMenuView class
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
    
	foreach( $multistatements as $file_index => $statements )
	{
	    // Get file ID for this set of statements
	    $file_id = isset($uploaded_file_ids[$file_index]) ? $uploaded_file_ids[$file_index] : null;
	    
	    foreach($statements as $id => $smt) {
		echo "importing statement {$smt->statementId} ...";
		echo importStatement($smt, $file_id);  // Pass file_id
		echo "\n";
	    }
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
* @param int|null file_id Optional uploaded file ID to link
* @return string summary of import (errors, imports, updates)
*************************************************************/
function importStatement($smt, $file_id = null) 
{
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
/* */
	
	// Mantis #2708: Link uploaded file to statement (Phase 2 refactored)
	if ($file_id !== null) {
		try {
			$uploadService = FileUploadService::create();
			$uploadService->linkToStatements($file_id, array($smt_id));
		} catch (\Exception $e) {
			display_error("Failed to link file to statement: " . $e->getMessage());
		}
	}
	
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
		bank_accounts_list_row($label, 'bank_account', $selected_id=null, $submit_on_change=false);
	    break;

	}
    }
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


function parse_uploaded_files() {
    start_table(TABLESTYLE);
    start_row();
    

    echo "<td width=100%><pre>\n";

    // Mantis #2708: Initialize file upload service (Phase 2 refactored)
    $uploadService = FileUploadService::create();
	$uploaded_file_ids = [];
	$has_blocked_duplicates = false;  // Track if any files were blocked
	$pending_duplicates = [];         // Track duplicates requiring user decision
	$force_upload_all = !empty($_POST['force_upload_all']);

    // initialize parser class
    $parserClass = $_POST['parser'] . '_parser';
    $parser = new $parserClass;

    //prepare static data for parser
    $static_data = array();
    $_parsers = getParsers();
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

    foreach($_FILES['files']['name'] as $id=>$fname) {
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

	    	        $companyBase = rtrim(company_path(), '/\\');
	    	        $pendingDir = $companyBase . DIRECTORY_SEPARATOR . 'bank_imports' . DIRECTORY_SEPARATOR . 'pending';
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

	    	        // Skip parsing/importing this file until user decides
	    	        continue;
    	        } else {
    	            // Block mode - hard reject
    	            display_error("BLOCKED: " . $result->getMessage());
    	            $has_blocked_duplicates = true;
    	            $smt_err++;
    	            continue;
    	        }
    	    } else {
    	        // Upload failed
    	        display_error("Upload failed: " . $result->getMessage());
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
			'duplicates' => $pending_duplicates,
		];

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
    }
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
	$parserType = $pending['parser'];
	$bank_account_id = $pending['bank_account'];
	$multistatements = !empty($pending['multistatements']) ? unserialize($pending['multistatements']) : [];
	$uploaded_file_ids = !empty($pending['uploaded_file_ids']) ? $pending['uploaded_file_ids'] : [];
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
				$smt_err++;
				continue;
			}

			$uploaded_file_ids[$idx] = $result->getFileId();
			display_notification(_("File saved with ID") . ': ' . $result->getFileId());

			$bom = pack('CCC', 0xEF, 0xBB, 0xBF);
			if (strncmp($content, $bom, 3) === 0) {
				$content = substr($content, 3);
			}

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
		} catch (\Exception $e) {
			display_error(_("Failed to force upload") . ' ' . $dup['filename'] . ': ' . $e->getMessage());
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
if (@$_POST['upload'] && ($_FILES['files']['error'][0] == 0)) {
    parse_uploaded_files();
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

//if import is hit, perform the import
if (@$_POST['import']) {
    import_statements();
}


end_form(2);

end_page();
?>
