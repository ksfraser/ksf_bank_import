<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionFactory [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionFactory.
 */
namespace Ksfraser\FaBankImport;

interface TransactionFactory
{	
	//throws InvalidTransactionType
	//function makeTransaction( $recordType ) : Transaction;
	function makeTransaction( array $transaction );
}
