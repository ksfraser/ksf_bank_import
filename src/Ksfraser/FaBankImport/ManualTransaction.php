<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Views\AddNoButton;

use Ksfraser\FaBankImport\Transaction;

/**
 * @BABOK FR-PROC-004, FR-PROC-013 | BR-PROC-001 | UAT-PROC-001
 */

/**
 * @BABOK FR-PROC-008 | BR-PROC-002 | UAT-PROC-008
 */

class ManualTransaction extends Transaction
{
	/**
 * @BABOK FR-PROC-008 | BR-PROC-002 | UAT-PROC-008
	 */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Manually Match A transaction";
	}
	/**
 * @BABOK FR-PROC-008 | BR-PROC-002 | UAT-PROC-008
	 */
	function displayPartner()
	{
	}
	/**
 * @BABOK FR-PROC-008 | BR-PROC-002 | UAT-PROC-008
	 */
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
