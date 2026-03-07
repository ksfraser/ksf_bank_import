<?php
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
