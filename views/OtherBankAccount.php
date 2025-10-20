<?php

use Ksfraser\HTML\LabelRowBase;

/**
 * OtherBankAccount - Display other party's bank account row
 * 
 * Shows the counterparty's bank account number and name.
 * Format: "ACCOUNT_NUMBER / ACCOUNT_NAME"
 * 
 * @package Views
 * @since 20251019 - Added use statement, PHPDoc
 */
class OtherBankAccount extends LabelRowBase
{
	/**
	 * Create other bank account row
	 * 
	 * @param object $bi_lineitem The bank import line item with otherBankAccount properties
	 */
	function __construct( $bi_lineitem )
	{
		// Set properties BEFORE calling parent::__construct()
		$this->label = "Other Bank Account:";
		$this->data = $bi_lineitem->otherBankAccount . ' / '. $bi_lineitem->otherBankAccountName;
		
		parent::__construct( "" );
	}
}
