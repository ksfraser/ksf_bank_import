<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: BR-009 / ARCH-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK FR-PROC-001, FR-PROC-006 | BR-PROC-001 | UAT-PROC-001

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
 * @BABOK FR-PROC-001, FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
	 * Create amount/charges row
	 * 
	 * @param object $bi_lineitem The bank import line item with amount, charge, currency properties
	 */
	function __construct( $bi_lineitem )
	{
		// Set properties BEFORE calling parent::__construct()
		$this->label = "Amount/Charge(s):";
		$this->data =  $bi_lineitem->getAmount() .' / ' . $bi_lineitem->getCharge() . " (" . $bi_lineitem->getCurrency() .")";
		
		parent::__construct( "" );
	}
}
