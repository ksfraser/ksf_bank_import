<?php

use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * OurBankAccount - Display our bank account details row
 * 
 * Shows the account number, bank name, account name, and account code.
 * Format: "ACC - BANK_NAME (ACCOUNT_NAME)(ACCOUNT_CODE)"
 * 
 * @package Views
 * @since 20251019 - Added use statement, PHPDoc
 */
class OurBankAccount extends LabelRowBase
{
	/**
	 * Create our bank account row
	 * 
	 * @param object $bi_lineitem The bank import line item with account properties
	 */
	function __construct( $bi_lineitem )
	{
		// Set properties BEFORE calling parent::__construct()
		$this->label = "Our Bank Account - (Account Name)(Number):";
		$this->data = $bi_lineitem->our_account . ' - ' . $bi_lineitem->ourBankDetails['bank_name'] . " (" . $bi_lineitem->ourBankAccountName . ")(" . $bi_lineitem->ourBankAccountCode . ")";
		
		parent::__construct( "" );
	}
}


