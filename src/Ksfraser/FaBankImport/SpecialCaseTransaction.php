<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Views\AddNoButton;

use Ksfraser\FaBankImport\Transaction;

/**
 * @BABOK FR-PROC-004, FR-PROC-013 | BR-PROC-001 | UAT-PROC-001
 */

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */

class SpecialCaseTransaction extends Transaction
{
	/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "";
	}
	/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function displayPartner()
	{
	}
	/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
