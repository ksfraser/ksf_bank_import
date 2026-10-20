<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK CLASS ROLLUP: TransType — 1 methods; FR-009 (1); IR-001 (1)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/TransType.php; coverage: docs/TestCases/ (file rollup)
 * TransType - Display transaction type row
 * 
 * Shows the transaction type based on transactionDC field:
 * - 'C' = Credit
 * - 'B' = Bank Transfer
 * - 'D' = Debit (default)
 * 
 * @package Views
 * @since 20251019 - Fixed property assignment, added use statement, PHPDoc
 */
class TransType extends LabelRowBase
{
	/**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
	 * Create transaction type row
	 * 
	 * @param object $bi_lineitem The bank import line item with transactionDC property
	 */
	function __construct( $bi_lineitem )
	{
		// Set properties BEFORE calling parent::__construct()
		$this->label = "Trans type:";
		$this->data = $bi_lineitem->getTransactionTypeLabel();
		
		parent::__construct( "" );
	}
}
