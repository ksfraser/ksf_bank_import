<?php

namespace Ksfraser\FaBankImport;

interface TransactionFactory
{	
	//throws InvalidTransactionType
	//function makeTransaction( $recordType ) : Transaction;
	function makeTransaction( array $transaction );
}
