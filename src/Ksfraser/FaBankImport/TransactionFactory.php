<?php

namespace Ksfraser\FaBankImport;

/**
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: TransactionFactory.php; coverage: docs/TestCases/ (file rollup)
 */
interface TransactionFactory
{	
	//throws InvalidTransactionType
	//function makeTransaction( $recordType ) : Transaction;
	/** @BABOK BR-002 / FR-008 / FR-018 — method: makeTransaction */
	function makeTransaction( array $transaction );
}
