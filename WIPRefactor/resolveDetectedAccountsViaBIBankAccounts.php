<?php
/**
 * Resolve detected OFX ACCTID to a FA bank_account_number using the module-owned
 * bi_bank_accounts xref table (if present).
 *
 * @param array<int,string> $detectedAccounts
 * @return array<string,string> map detectedAcctid => FA bank_account_number
 */
function resolve_detected_accounts_via_bi_bank_accounts(array $detectedAccounts): array
{
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	return bi_bank_accounts_model::resolve_detected_accounts_to_bank_account_numbers($detectedAccounts);
}
