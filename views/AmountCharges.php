<?php

use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * AmountCharges - Display transaction amount and charges row
 * 
 * Shows the transaction amount, charges, and currency.
 * Format: "AMOUNT / CHARGE (CURRENCY)"
 * 
 * @package Views
 * @since 20251019 - Fixed missing property assignment, added use statement, PHPDoc
 */
class AmountCharges extends LabelRowBase
{
	/**
	 * Create amount/charges row
	 * 
	 * @param object $bi_lineitem The bank import line item with amount, charge, currency properties
	 */
	function __construct( $bi_lineitem )
	{
		// Set properties BEFORE calling parent::__construct()
		$this->label = "Amount / Charge(s):";
		$this->data =  $bi_lineitem->amount .' / ' . $bi_lineitem->charge . " (" . $bi_lineitem->currency .")";
		
		parent::__construct( "" );
	}
}
