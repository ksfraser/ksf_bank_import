<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\TransactionFactory;
use Ksfraser\FaBankImport\SupplierTransaction;
use Ksfraser\FaBankImport\CustomerTransaction;
use Ksfraser\FaBankImport\FaBankTransferTransaction;
use Ksfraser\FaBankImport\QuickEntryTransaction;
use Ksfraser\FaBankImport\MatchedTransaction;
use Ksfraser\FaBankImport\ManualTransaction;
use Ksfraser\FaBankImport\SpecialCaseTransaction;

/**
 * @BABOK FR-PROC-002, FR-PROC-004, FR-PROC-005, FR-PROC-006, FR-PROC-007, FR-PROC-008, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
 */
class TransactionFactoryImp implements TransactionFactory
{
	/**
 * @BABOK FR-PROC-002, FR-PROC-004, FR-PROC-005, FR-PROC-006, FR-PROC-007, FR-PROC-008, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct()
	{
	}
	/**
 * @BABOK FR-PROC-002, FR-PROC-004, FR-PROC-005, FR-PROC-006, FR-PROC-007, FR-PROC-008, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
	 */
	function makeTransaction( array $transaction ): Transaction
	{
		if( ! isset( $_POST['partnerType'][ $transaction['id'] ] ) )
		{
			switch( $transaction['transactionDC'] )
			{
				case 'C':
					$partnerType = 'CU';
				break;
				case 'D':
					$partnerType = 'SP';
				break;
				case 'B':
					$partnerType = 'BT';
				break;
				default:
					$partnerType = 'QE';
				break;
			}
			$_POST['partnerType'][ $transaction['id'] ] = $partnerType;
		}
		else
		{
			display_notification( __FILE__ . "::" . __LINE__ . "::" . print_r( $_POST['partnerType'][ $transaction['id'] ], true ) );
		}
		switch( $_POST['partnerType'][ $transaction['id'] ] ) 
		{
			case 'SP':
				return new SupplierTransaction( $transaction );
				break;
			case 'CU':
				return new CustomerTransaction( $transaction );
				break;
			case 'BT':      //partnerType
				return new BankTransferTransaction( $transaction );
				break;
			// quick entry
			case 'QE':      //partnerType
				return new QuickEntryTransaction( $transaction );
				break;
			case 'MA':      //partnerType - manual settlement
				return new ManualTransaction( $transaction );
				break;
			case 'ZZ':      //partnerType - matched existing transaction
				return new MatchedTransaction( $transaction );
				break;
			default:
				return new SpecialCaseTransaction( $transaction );
		}
	}
}

