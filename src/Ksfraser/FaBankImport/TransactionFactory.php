<?php

namespace Ksfraser\FaBankImport;

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
interface TransactionFactory
{	
	//throws InvalidTransactionType
	//function makeTransaction( $recordType ) : Transaction;
	/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function makeTransaction( array $transaction );
}
