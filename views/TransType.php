<?php

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
		switch( $bi_lineitem->transactionDC )
		{
			case 'C':
				$typeLabel = "Credit";
			break;
			case 'B':
				$typeLabel = "Bank Transfer";
			break;
			case 'D':
			default:
				$typeLabel = "Debit";
			break;
		}
		
		// Set properties BEFORE calling parent::__construct()
		$this->label = "Trans Type:";
		$this->data = $typeLabel;
		
		parent::__construct( "" );
	}
}
