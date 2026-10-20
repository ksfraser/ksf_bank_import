<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK CLASS ROLLUP: TransTitle — 1 methods; FR-009 (1); IR-001 (1)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/TransTitle.php; coverage: docs/TestCases/ (file rollup)
 * TransTitle - Display transaction title row
 * 
 * Shows the transaction title/description from the bank statement.
 * 
 * @package Views
 * @since 20251019 - Fixed missing property assignment, added use statement, PHPDoc
 */
class TransTitle extends LabelRowBase
{
	/**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
	 * Create transaction title row
	 * 
	 * @param object $bi_lineitem The bank import line item with transactionTitle property
	 */
	function __construct( $bi_lineitem )
	{
		// Set properties BEFORE calling parent::__construct()
		$this->label = "Trans Title:";
		$this->data =  $bi_lineitem->getTransactionTitle();
		
		parent::__construct( "" );
	}
}
