<?php
function bi_bank_accounts_meta_differs(array $existing, array $desired): bool
{
	$fields = ['acctid', 'bankid', 'intu_bid', 'curdef'];
	foreach ($fields as $f) {
		$ex = isset($existing[$f]) ? trim((string)$existing[$f]) : '';
		$de = isset($desired[$f]) ? trim((string)$desired[$f]) : '';
		if ($de === '') {
			continue;
		}
		if ($ex !== $de) {
			return true;
		}
	}
	return false;
}
