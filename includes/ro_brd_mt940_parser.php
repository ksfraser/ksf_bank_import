<?php
/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

class ro_brd_mt940_parser extends mt940_parser {
    // brd uses :OS: to asend some info
    var $statementSplit = '/^(:20:.*?)(?=^:20:|:OS:|\Z)/sm';

    //brd puts sf 20, 31, 32 inline somewhere
    var $sf_20 = '/\+20([^+\n]+)/';
    var $sf_31 = '/\+31([^+\n]+)/';
    var $sf_32 = '/\+32([^+\n]+)/';

    //brd adds at the end of account a /
    var $sf_33 = '/\+33(.+)\//';

    function __construct() {
	parent::__construct();
	$this->statementMap['bank'] = array('value'=>'BRD');
    }


    function postProcessTransaction(&$trz) {
	//replace , with .
	$trz->transactionAmount = str_replace(',', '.', $trz->transactionAmount);

	$y = substr($trz->valueTimestamp, 0, 2);
	$m = substr($trz->valueTimestamp, 2, 2);
	$d = substr($trz->valueTimestamp, 4, 2);
	$y += 2000;
	$trz->valueTimestamp = $y . '-' . $m . '-' . $d;

	$m = substr($trz->entryTimestamp, 0, 2);
	$d = substr($trz->entryTimestamp, 2, 2);
	$trz->entryTimestamp = $y . '-' . $m . '-' . $d;
    }

    function postProcessStatement(&$smt) {
	//replace , with .
	$smt->startBalance = str_replace(',', '.', $smt->startBalance);
	$smt->endBalance = str_replace(',', '.', $smt->endBalance);

	//change statement date
	$y = substr($smt->timestamp, 0, 2);
	$m = substr($smt->timestamp, 2, 2);
	$d = substr($smt->timestamp, 4, 2);
	$y += 2000;
	$smt->timestamp = $y . '-' . $m . '-' . $d;

	//add statement id field as $y-$number-$sequence
	$smt->statementId = "{$smt->timestamp}-{$smt->number}-{$smt->sequence}";

	//fucking amazing: same transaction code in the same statement happening at BRD-RO
	//search back in transactions, if same type and code, add some character here
	$i=0;
	foreach($smt->transactions as $t) {
	    do {
		//Search back in transactions to check for same code
		//echo "searching back for code=".$t->transactionCode." type=".$t->transactionType."\n";
		$j=0; $exists = false;
		foreach($smt->transactions as $tback) {
		    if ($i == $j) {
			//echo "    not found\n";
			break;
		    }
		    //echo "  checking against code=".$tback->transactionCode." type=".$tback->transactionType."\n";
		    if (($tback->transactionType == $t->transactionType) && ($tback->transactionCode == $t->transactionCode)) {
			//echo "    exists! add x and go again\n";
			$exists = true;
			$t->transactionCode .='X';
			break;
		    }
		    $j++;
		}
	    } while($exists == true);
	    $i++;
	}

	// Extract contacts for all transactions in this statement
	foreach($smt->transactions as &$trz) {
	    $this->extractContactForTransaction($trz);
	}
    }

    /**
     * Extract/create contact for BRD MT940 transaction.
     *
     * Integrates with ContactService and ContactDeduplicationService to maintain
     * a persistent contact database linked to bank transactions.
     *
     * Design: Non-blocking. Errors caught and logged but don't interrupt parsing.
     *
     * @param transaction $trz The bank_import transaction object being populated
     * @return void
     */
    private function extractContactForTransaction($trz): void
    {
        // Determine merchant name (try multiple fields)
        $merchant = null;
        if (!empty($trz->merchant)) {
            $merchant = $trz->merchant;
        } elseif (!empty($trz->account)) {
            $merchant = $trz->account;
        } elseif (!empty($trz->accountName1)) {
            $merchant = $trz->accountName1;
        } elseif (!empty($trz->transactionTitle1)) {
            $merchant = $trz->transactionTitle1;
        }

        // Only attempt extraction if we have merchant name
        if (empty($merchant)) {
            return;
        }

        try {
            // Graceful degradation: if db not available, skip contact extraction
            if (!isset($GLOBALS['db'])) {
                return;
            }

            $db = $GLOBALS['db'];

            // Load services (lazy load to avoid mandatory dependency)
            if (!class_exists('\Ksfraser\FaBankImport\Services\ContactService')) {
                return;
            }

            $contactService = new \Ksfraser\FaBankImport\Services\ContactService($db);
            $deduplicateService = new \Ksfraser\FaBankImport\Services\ContactDeduplicationService($contactService);

            // Determine contact type based on transaction direction
            // DEBIT (outgoing) = supplier; CREDIT (incoming) = customer
            $contactType = ($trz->transactionDC === 'D') ? 'supplier' : 'customer';

            // Prepare contact data from merchant
            $contactData = new \Ksfraser\Contact\DTO\ContactData();
            $contactData->fromArray([
                'name' => (string) $merchant,
                'contact_type' => $contactType,
            ]);

            // Get or create contact with deduplication
            $contact = $deduplicateService->getOrCreateWithDeduplicate($contactData);

            // Link transaction to contact if creation succeeded
            if ($contact && !empty($contact->id)) {
                $trz->contact_id = (int) $contact->id;
            }

        } catch (\Throwable $e) {
            // Non-blocking error handling: Log but don't fail the import
            error_log('BRD MT940 contact extraction failed: ' . $e->getMessage());
        }
    }
    
}
