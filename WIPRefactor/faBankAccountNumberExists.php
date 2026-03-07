<?php
/**
 * Determine if a bank account number exists in FA.
 *
 * @param string $bankAccountNumber
 * @return bool
 */
function fa_bank_account_number_exists(string $bankAccountNumber): bool
{
	$bankAccountNumber = trim($bankAccountNumber);
	if ($bankAccountNumber === '') {
		return false;
	}
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	return bi_bank_accounts_model::fa_get_bank_account_id_by_number($bankAccountNumber) !== null;
}
