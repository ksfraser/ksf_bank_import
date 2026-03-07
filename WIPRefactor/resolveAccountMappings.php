<?php
function resolve_account_mappings() {
	if (empty($_SESSION['bank_import_account_resolution'])) {
        \Ksfraser\FaBankImport\Views\AccountResolutionErrorView::render();
        return;
    }
	$username = UserSession::getCurrentUsername();
	start_table(TABLESTYLE);
	start_row();
	echo "<td width=100%><pre>\n";

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
			$repo->set($key, (string)$selectedId, $username, 'Associate detected account to FA bank account');
			$rememberedCount++;
		}
		
		// Back-fill metadata in bi_uploaded_files if we have file IDs
		if (!empty($uploaded_file_ids)) {
			try {
				$uploadService = FileUploadService::create();
				foreach ($pending['unresolved'][$detected] ?? [] as $fileIndex) {
					$fileId = $uploaded_file_ids[$fileIndex] ?? null;
					if ($fileId !== null) {
						$uploadService->updateBankAccountId((int)$fileId, (int)$selectedId);
					}
				}
			} catch (\Throwable $e) {
				// Non-blocking metadata update
			}
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
