<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Transaction;

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
class QuickEntryTransaction extends Transaction
{
	/**
 * @BABOK FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Quick Entry";
	}
	/**
 * @BABOK FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
	 */
	function displayPartner()
	{
	}
	/**
 * @BABOK FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
	 */
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
/**
 * @BABOK FR-PROC-009 | BR-PROC-002 | UAT-PROC-008
 */
class MatchedTransaction extends Transaction
{
	/**
 * @BABOK FR-PROC-009 | BR-PROC-002 | UAT-PROC-008
	 */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Matched Existing Transaction";
	}
	/**
 * @BABOK FR-PROC-009 | BR-PROC-002 | UAT-PROC-008
	 */
	function displayPartner()
	{
	}
	/**
 * @BABOK FR-PROC-009 | BR-PROC-002 | UAT-PROC-008
	 */
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
/**
 * @BABOK FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
 */
class BankTransferTransaction extends Transaction
{
	/**
 * @BABOK FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Bank Transfer";
	}
	/**
 * @BABOK FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
	 */
	function displayPartner()
	{
	}
	/**
 * @BABOK FR-PROC-007 | BR-PROC-001 | UAT-PROC-001
	 */
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
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

