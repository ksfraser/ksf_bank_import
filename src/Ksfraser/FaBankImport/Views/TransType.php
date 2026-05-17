<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\LabelRowBase;

/**
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