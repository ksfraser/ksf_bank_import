<?php

require_once(__DIR__ . '/../ksf_modules_common/defines.inc.php');

/**
 * bi_bank_accounts_model
 *
 * Lightweight access layer for the module-owned `bi_bank_accounts` table plus
 * helper lookups needed by the import flow.
 */
class bi_bank_accounts_model
{
	public static function table_name(): string
	{
		return TB_PREF . 'bi_bank_accounts';
	}

	public static function table_exists(): bool
	{
		$table = self::table_name();
		$res = db_query('SHOW TABLES LIKE ' . db_escape($table), 'Could not check bi_bank_accounts existence');
		return db_num_rows($res) > 0;
	}

	public static function get_row(int $bankAccountId): ?array
	{
		$bankAccountId = (int)$bankAccountId;
		if ($bankAccountId <= 0 || !self::table_exists()) {
			return null;
		}
		$table = self::table_name();
		$sql = "SELECT bank_account_id, intu_bid, bankid, acctid, accttype, curdef
			FROM `{$table}`
			WHERE bank_account_id=" . (int)$bankAccountId . "
			LIMIT 1";
		$res = db_query($sql, 'Could not load bi_bank_accounts row');
		$row = db_fetch($res);
		return is_array($row) ? $row : null;
	}

	/**
	 * @param array{acctid?:string,bankid?:string,intu_bid?:string,accttype?:string,curdef?:string} $meta
	 */
	public static function upsert(int $bankAccountId, array $meta): void
	{
		if (!self::table_exists()) {
			return;
		}
		$bankAccountId = (int)$bankAccountId;
		if ($bankAccountId <= 0) {
			return;
		}
		$table = self::table_name();

		$intu_bid = isset($meta['intu_bid']) ? trim((string)$meta['intu_bid']) : '';
		$bankid = isset($meta['bankid']) ? trim((string)$meta['bankid']) : '';
		$acctid = isset($meta['acctid']) ? trim((string)$meta['acctid']) : '';
		$accttype = isset($meta['accttype']) ? trim((string)$meta['accttype']) : '';
		$curdef = isset($meta['curdef']) ? trim((string)$meta['curdef']) : '';

		// Avoid inserting meaningless "blank identity" rows.
		if ($acctid === '' && $bankid === '' && $intu_bid === '') {
			return;
		}

		$findSql = "SELECT id FROM `{$table}`
			WHERE IFNULL(acctid,'')=" . db_escape($acctid) . "
			  AND IFNULL(bankid,'')=" . db_escape($bankid) . "
			  AND IFNULL(intu_bid,'')=" . db_escape($intu_bid) . "
			LIMIT 1";
		$res = db_query($findSql, 'Could not query bi_bank_accounts existing mapping');
		$row = db_fetch($res);
		$existingId = (is_array($row) && isset($row['id'])) ? (int)$row['id'] : 0;

		if ($existingId > 0) {
			$updateSql = "UPDATE `{$table}`
				SET bank_account_id=" . (int)$bankAccountId . ",
					updated_ts=CURRENT_TIMESTAMP,
					acctid=" . db_escape($acctid) . ",
					bankid=" . db_escape($bankid) . ",
					intu_bid=" . db_escape($intu_bid) . ",
					accttype=" . db_escape($accttype) . ",
					curdef=" . db_escape($curdef) . "
				WHERE id=" . (int)$existingId;
			db_query($updateSql, 'Could not update bi_bank_accounts mapping');
			return;
		}

		$insertSql = "INSERT INTO `{$table}` (bank_account_id, updated_ts, intu_bid, bankid, acctid, accttype, curdef)
			VALUES (" . (int)$bankAccountId . ", CURRENT_TIMESTAMP, " . db_escape($intu_bid) . ", " . db_escape($bankid) . ", " . db_escape($acctid) . ", " . db_escape($accttype) . ", " . db_escape($curdef) . ")";
		db_query($insertSql, 'Could not insert bi_bank_accounts mapping');
	}

	/**
	 * Resolve FA bank account id by bank_account_number.
	 *
	 * IMPORTANT: No raw SQL here; we delegate to FA/ksf_modules_common helpers.
	 */
	public static function fa_get_bank_account_id_by_number(string $bankAccountNumber): ?int
	{
		$bankAccountNumber = trim($bankAccountNumber);
		if ($bankAccountNumber === '') {
			return null;
		}

		// Prefer FA helper if available in runtime.
		if (function_exists('fa_get_bank_account_by_number')) {
			$row = fa_get_bank_account_by_number($bankAccountNumber);
			if (is_array($row) && isset($row['id'])) {
				$id = (int)$row['id'];
				return $id > 0 ? $id : null;
			}
		}

		// Fall back to our refactored lookup helper.
		require_once(__DIR__ . '/src/Ksfraser/FaBankImport/models/BankAccountByNumber.php');
		$lookup = new \Ksfraser\FaBankImport\models\BankAccountByNumber($bankAccountNumber);
		$details = $lookup->getBankDetails();
		if (is_array($details) && isset($details['id'])) {
			$id = (int)$details['id'];
			return $id > 0 ? $id : null;
		}

		return null;
	}

	/**
	 * Resolve detected OFX ACCTID values to FA bank_account_number values using
	 * the module-owned bi_bank_accounts xref.
	 *
	 * Only returns mappings where exactly one FA bank account id is referenced
	 * for a given detected acctid.
	 *
	 * @param array<int,string> $detectedAccounts
	 * @return array<string,string> map detectedAcctid => FA bank_account_number
	 */
	public static function resolve_detected_accounts_to_bank_account_numbers(array $detectedAccounts): array
	{
		$detectedAccounts = array_values(array_filter(array_map('trim', $detectedAccounts), function ($v) {
			return is_string($v) && $v !== '';
		}));
		if (empty($detectedAccounts) || !self::table_exists()) {
			return [];
		}

		$biTable = self::table_name();
		$faTable = TB_PREF . 'bank_accounts';

		$in = [];
		foreach ($detectedAccounts as $detected) {
			$in[] = db_escape($detected);
		}

		$sql = "SELECT bb.acctid AS detected,
				MIN(b.bank_account_number) AS bank_account_number,
				COUNT(DISTINCT bb.bank_account_id) AS mapped_count
			FROM `{$biTable}` bb
			JOIN `{$faTable}` b ON b.id = bb.bank_account_id
			WHERE bb.acctid IN (" . implode(',', $in) . ")
			GROUP BY bb.acctid
			HAVING mapped_count = 1";
		$res = db_query($sql, 'Could not resolve detected accounts via bi_bank_accounts');

		$map = [];
		while ($row = db_fetch($res)) {
			$detected = isset($row['detected']) ? trim((string)$row['detected']) : '';
			$bankAccountNumber = isset($row['bank_account_number']) ? trim((string)$row['bank_account_number']) : '';
			if ($detected !== '' && $bankAccountNumber !== '') {
				$map[$detected] = $bankAccountNumber;
			}
		}

		return $map;
	}
}
