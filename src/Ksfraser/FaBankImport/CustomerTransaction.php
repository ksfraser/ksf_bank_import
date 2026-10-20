<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Transaction;
use Ksfraser\FaBankImport\AddCustomerButton;

/**
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: CustomerTransaction.php; coverage: docs/TestCases/ (file rollup)
 */
class CustomerTransaction extends Transaction
{
	/** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Customer Payment";
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: displayPartner */
	function displayPartner()
	{
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: selectAndDisplayButton */
	function selectAndDisplayButton()
	{
		$b = new AddCustomerButton( $this->id );
		$b->toHtml();
	}
}
