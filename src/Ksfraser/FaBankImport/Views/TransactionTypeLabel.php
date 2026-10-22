<?php

namespace Ksfraser\FaBankImport\Views;

/**//*****************************************************************
*Return the appropriate Transaction Type Label
*
*	Changing to be SRP and IoC compliant.
*
*@since 20250515
**********************************************************************/
/**
 * @BABOK FR-PROC-001, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 */
class TransactionTypeLabel
{
	private $transactionTypeLabel;
	/**
 * @BABOK FR-PROC-001, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $transactionDC )
	{
		//This could probably be moved to a getTransactionTypeLabel function
		switch( $transactionDC )
		{
			case 'C':
				$this->transactionTypeLabel = "Credit";
			break;
			case 'B':
				$this->transactionTypeLabel = "Bank Transfer";
			break;
			case 'D':
			default:
				$this->transactionTypeLabel = "Debit";
			break;
		}
		//return $transactionTypeLabel;
	}
	/**
 * @BABOK FR-PROC-001, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
	 */
	function getTransactionTypeLabel()
	{
		return $this->transactionTypeLabel;
	}
}
