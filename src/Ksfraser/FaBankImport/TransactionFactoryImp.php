<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionFactoryImp [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionFactoryImp.
 */
namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\TransactionFactory;
use Ksfraser\FaBankImport\SupplierTransaction;
use Ksfraser\FaBankImport\CustomerTransaction;
use Ksfraser\FaBankImport\FaBankTransferTransaction;
use Ksfraser\FaBankImport\QuickEntryTransaction;
use Ksfraser\FaBankImport\MatchedTransaction;
use Ksfraser\FaBankImport\ManualTransaction;
use Ksfraser\FaBankImport\SpecialCaseTransaction;

class TransactionFactoryImp implements TransactionFactory
{
	function __construct()
	{
	}
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
			case 'MA':
				return new MatchedTransaction( $transaction );
				break;
			case 'ZZ':      //partnerType
				return new ManualTransaction( $transaction );
				break;
			default:
				return new SpecialCaseTransaction( $transaction );
		}
	}
}

