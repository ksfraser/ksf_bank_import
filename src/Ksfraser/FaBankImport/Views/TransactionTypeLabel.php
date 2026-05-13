<?php

namespace Ksfraser\FaBankImport;


/**//*****************************************************************
*Return the appropriate Transaction Type Label
*
*	Changing to be SRP and IoC compliant.
*
*@since 20250515
**********************************************************************/
class TransactionTypeLabel
{
	private $transactionTypeLabel;
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
	function getTransactionTypeLabel()
	{
		return $this->transactionTypeLabel;
	}
}
