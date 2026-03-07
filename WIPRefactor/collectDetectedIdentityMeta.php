<?php
/**
 * Collect detected identity metadata for each detected acctid.
 *
 * @return array<string,array{acctid:string,bankid:string,intu_bid:string,curdef:string,accttype:string}>
 */
function collect_detected_identity_meta(array $multistatements): array
{
	$metaByDetected = [];
	foreach ($multistatements as $statements) {
		if (!is_array($statements)) {
			continue;
		}
		foreach ($statements as $smt) {
			if (!is_object($smt)) {
				continue;
			}
			$detectedAcctid = isset($smt->acctid) ? trim((string)$smt->acctid) : '';
			if ($detectedAcctid === '') {
				continue;
			}
			if (!isset($metaByDetected[$detectedAcctid])) {
				$metaByDetected[$detectedAcctid] = [
					'acctid' => $detectedAcctid,
					'bankid' => isset($smt->bankid) ? trim((string)$smt->bankid) : '',
					'intu_bid' => isset($smt->intu_bid) ? trim((string)$smt->intu_bid) : '',
					'curdef' => isset($smt->currency) ? trim((string)$smt->currency) : '',
					'accttype' => '',
				];
				continue;
			}
			if ($metaByDetected[$detectedAcctid]['bankid'] === '' && isset($smt->bankid)) {
				$metaByDetected[$detectedAcctid]['bankid'] = trim((string)$smt->bankid);
			}
			if ($metaByDetected[$detectedAcctid]['intu_bid'] === '' && isset($smt->intu_bid)) {
				$metaByDetected[$detectedAcctid]['intu_bid'] = trim((string)$smt->intu_bid);
			}
			if ($metaByDetected[$detectedAcctid]['curdef'] === '' && isset($smt->currency)) {
				$metaByDetected[$detectedAcctid]['curdef'] = trim((string)$smt->currency);
			}
		}
	}
	return $metaByDetected;
}
