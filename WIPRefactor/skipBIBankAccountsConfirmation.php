<?php
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
