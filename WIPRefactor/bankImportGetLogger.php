<?php
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