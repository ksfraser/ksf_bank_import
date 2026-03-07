<?php
function confirm_bi_bank_accounts_mappings(): void
{
	// Deprecated: bi_bank_accounts mappings are now persisted during account resolution.
	unset($_SESSION['bank_import_bi_bank_accounts_confirm']);
	return;
}
