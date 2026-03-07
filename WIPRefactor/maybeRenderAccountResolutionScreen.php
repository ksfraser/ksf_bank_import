<?php
/**
 * Render the account-resolution UI if needed.
 *
 * Returns true if it rendered a blocking screen and caller should return early.
 */
function maybe_render_account_resolution_screen($parserType, $bankAccountId, array &$multistatements, array $uploaded_file_ids, array $uploaded_filenames): bool
{
	$mappingService = new StatementAccountMappingService();
	$logger = bank_import_get_logger();


	// Always require user to confirm/resolve detected accounts, even if mappings exist.
	// Collect all detected accounts (unique), grouped by file index.
	$detectedByFile = $mappingService->collectDetectedAccountsByFile($multistatements);
	$unresolved = [];
	foreach ($detectedByFile as $fileIndex => $detectedList) {
		foreach ($detectedList as $detected) {
			$detected = trim((string)$detected);
			if ($detected === '') {
				continue;
			}
			if (!isset($unresolved[$detected])) {
				$unresolved[$detected] = [];
			}
			$unresolved[$detected][(int)$fileIndex] = true;
		}
	}

	// Always show the account resolution screen if any detected accounts exist.
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
