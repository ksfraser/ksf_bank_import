<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Transaction;

/**
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: FactoryTransactions.php; coverage: docs/TestCases/ (file rollup)
 */
class SupplierTransaction extends Transaction
{
	/** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Supplier Payment";
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: displayPartner */
	function displayPartner()
	{
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: selectAndDisplayButton */
	function selectAndDisplayButton()
	{
		$b = new AddVendorButton( $this->id );
		$b->toHtml();
	}
}
class QuickEntryTransaction extends Transaction
{
	/** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Quick Entry";
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: displayPartner */
	function displayPartner()
	{
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: selectAndDisplayButton */
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
class MatchedTransaction extends Transaction
{
	/** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Matched Existing Transaction";
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: displayPartner */
	function displayPartner()
	{
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: selectAndDisplayButton */
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
class BankTransferTransaction extends Transaction
{
	/** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Bank Transfer";
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: displayPartner */
	function displayPartner()
	{
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: selectAndDisplayButton */
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
class ManualTransaction extends Transaction
{
	/** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Manually Match A transaction";
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: displayPartner */
	function displayPartner()
	{
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: selectAndDisplayButton */
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
class SpecialCaseTransaction extends Transaction
{
	/** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "";
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: displayPartner */
	function displayPartner()
	{
	}
	/** @BABOK BR-002 / FR-008 / FR-018 — method: selectAndDisplayButton */
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}

