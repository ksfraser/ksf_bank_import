<?php

use Ksfraser\HTML\Composites\LabelRowBase;

/**
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
	 * Create transaction title row
	 * 
	 * @param object $bi_lineitem The bank import line item with transactionTitle property
	 */
	function __construct( $bi_lineitem )
	{
		// Set properties BEFORE calling parent::__construct()
		$this->label = "Transaction Title:";
		$this->data =  $bi_lineitem->transactionTitle;
		
		parent::__construct( "" );
	}
}
