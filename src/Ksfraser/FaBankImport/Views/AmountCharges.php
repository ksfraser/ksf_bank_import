<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK CLASS ROLLUP: AmountCharges — 1 methods; FR-009 (1); IR-001 (1)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/AmountCharges.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
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
