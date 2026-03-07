<?php
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
    	$bank_account_id = !empty($_POST['bank_account']) ? (int)$_POST['bank_account'] : null;
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
