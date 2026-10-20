<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/OtherBankAccount.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
	 * Create other bank account row
	 * 
	 * @param object $bi_lineitem The bank import line item with otherBankAccount properties
	 */
	function __construct( $bi_lineitem )
	{
		// Set properties BEFORE calling parent::__construct()
		$this->label = "Other account:";
		$this->data = $bi_lineitem->getOtherBankAccount() . ' / '. $bi_lineitem->getOtherBankAccountName();
		
		parent::__construct( "" );
	}
}
