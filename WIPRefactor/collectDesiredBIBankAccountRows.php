<?php
/**
 * Build desired bi_bank_accounts values from parsed statements.
 *
 * @return array<int,array{acctid:string,bankid:string,intu_bid:string,curdef:string,accttype:string,detected_acctid:string,bank_account_number:string}>
 */
function collect_desired_bi_bank_accounts_rows(array $multistatements): array
{
	$desired = [];
	foreach ($multistatements as $fileIndex => $statements) {
		if (!is_array($statements)) {
			continue;
		}
		foreach ($statements as $smt) {
			if (!is_object($smt)) {
				continue;
			}
			$detectedAcctid = isset($smt->acctid) ? trim((string)$smt->acctid) : '';
			$faAccountNumber = isset($smt->account) ? trim((string)$smt->account) : '';
			if ($detectedAcctid === '' || $faAccountNumber === '') {
				continue;
			}
			$bankAccountId = fa_get_bank_account_id_by_number($faAccountNumber);
			if ($bankAccountId === null) {
				continue;
			}
			if (isset($desired[$bankAccountId])) {
				// If multiple statements map to the same FA bank account, keep the first.
				continue;
			}
			$desired[$bankAccountId] = [
				'acctid' => $detectedAcctid,
				'bankid' => isset($smt->bankid) ? trim((string)$smt->bankid) : '',
				'intu_bid' => isset($smt->intu_bid) ? trim((string)$smt->intu_bid) : '',
				'curdef' => isset($smt->currency) ? trim((string)$smt->currency) : '',
				'accttype' => '',
				'detected_acctid' => $detectedAcctid,
				'bank_account_number' => $faAccountNumber,
			];
		}
	}
	return $desired;
}
