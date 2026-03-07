<?php
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

