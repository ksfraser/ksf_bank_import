<?php
/**
 * Resolve FA bank account id by bank_account_number.
 */
function fa_get_bank_account_id_by_number(string $bankAccountNumber): ?int
{
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	return bi_bank_accounts_model::fa_get_bank_account_id_by_number($bankAccountNumber);
}

function bi_bank_accounts_table_exists(): bool
{
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	return bi_bank_accounts_model::table_exists();
}

function bi_bank_accounts_get_row(int $bankAccountId): ?array
{
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	return bi_bank_accounts_model::get_row((int)$bankAccountId);
}

function bi_bank_accounts_upsert(int $bankAccountId, array $meta): void
{
	require_once(__DIR__ . '/class.bi_bank_accounts.php');
	bi_bank_accounts_model::upsert((int)$bankAccountId, $meta);
}
