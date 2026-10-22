<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Views\AddVendorButton;

use Ksfraser\FaBankImport\Transaction;

/**
 * @BABOK FR-PROC-004, FR-PROC-013 | BR-PROC-001 | UAT-PROC-001
 */

/**
 * @BABOK FR-PROC-004, FR-PROC-013 | BR-PROC-001 | UAT-PROC-001
 */

class SupplierTransaction extends Transaction
{
	/**
 * @BABOK FR-PROC-004, FR-PROC-013 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Supplier Payment";
	}
	/**
 * @BABOK FR-PROC-004, FR-PROC-013 | BR-PROC-001 | UAT-PROC-001
	 */
	function displayPartner()
	{
	}
	/**
 * @BABOK FR-PROC-004, FR-PROC-013 | BR-PROC-001 | UAT-PROC-001
	 */
	function selectAndDisplayButton()
	{
		$b = new AddVendorButton( $this->id );
		$b->toHtml();
	}
}
/**
 * @BABOK FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
 */
