<?php
function bank_import_log_dir(): string
{
	return BankImportPathResolver::forCurrentCompany()->logsDir();
}