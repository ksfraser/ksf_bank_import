<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK CLASS ROLLUP: OurBankAccount — 1 methods; FR-009 (1); IR-001 (1)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/OurBankAccount.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
	 * Create our bank account row
	 * 
	 * @param object $bi_lineitem The bank import line item with account properties
	 */
	function __construct( $bi_lineitem )
	{
		// Set properties BEFORE calling parent::__construct()
		$this->label = "Our Bank Account - (Account Name)(Number):";
		$this->data = $bi_lineitem->getOurAccount() . ' - ' . $bi_lineitem->getOurBankDetails()['bank_name'] . " (" . $bi_lineitem->getOurBankAccountName() . ")(" . $bi_lineitem->getOurBankAccountCode() . ")";
		
		parent::__construct( "" );
	}
}
