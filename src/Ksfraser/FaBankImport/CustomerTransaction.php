<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Transaction;
use Ksfraser\FaBankImport\AddCustomerButton;

/**
 * @BABOK FR-PROC-005, FR-PROC-013 | BR-PROC-001 | UAT-PROC-001
 */
class CustomerTransaction extends Transaction
{
	/**
 * @BABOK FR-PROC-005, FR-PROC-013 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Customer Payment";
	}
	/**
 * @BABOK FR-PROC-005, FR-PROC-013 | BR-PROC-001 | UAT-PROC-001
	 */
	function displayPartner()
	{
	}
	/**
 * @BABOK FR-PROC-005, FR-PROC-013 | BR-PROC-001 | UAT-PROC-001
	 */
	function selectAndDisplayButton()
	{
		$b = new AddCustomerButton( $this->id );
		$b->toHtml();
	}
}
