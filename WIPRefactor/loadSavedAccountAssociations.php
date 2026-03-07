<?php

/**
 * Apply any saved associations (detected acctid \u2192 FA bank account id) to parsed statements.
 * Returns a map of detectedAcct => resolved FA bank_account_number.
 *
 * @param array $multistatements
 * @return array<string,string>
 */
function load_saved_account_associations(array $multistatements): array
{
	$service = new StatementAccountMappingService();
	$repo = new DatabaseConfigRepository();
	$detectedByFile = $service->collectDetectedAccountsByFile($multistatements);

	$detectedAll = [];
	foreach ($detectedByFile as $list) {
		foreach ($list as $detected) {
			$detectedAll[$detected] = true;
		}
	}

	$resolved = [];
	foreach (array_keys($detectedAll) as $detected) {
		$key = DetectedAccountAssociationKey::forDetectedAccount($detected);
		$bankAccountId = $repo->get($key);
		if ($bankAccountId === null || $bankAccountId === '') {
			continue;
		}
		$bankAccountId = (int)$bankAccountId;
		if ($bankAccountId <= 0) {
			continue;
		}
		$ba = get_bank_account($bankAccountId);
		if (is_array($ba) && !empty($ba['bank_account_number'])) {
			$resolved[$detected] = $ba['bank_account_number'];
		}
	}

	return $resolved;
}
