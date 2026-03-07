<?php
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
