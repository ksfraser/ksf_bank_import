<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Transaction;

class SupplierTransaction extends Transaction
{
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Supplier Payment";
	}
	function displayPartner()
	{
	}
	function selectAndDisplayButton()
	{
		$b = new AddVendorButton( $this->id );
		$b->toHtml();
	}
}
class QuickEntryTransaction extends Transaction
{
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Quick Entry";
	}
	function displayPartner()
	{
	}
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
class MatchedTransaction extends Transaction
{
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Matched Existing Transaction";
	}
	function displayPartner()
	{
	}
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
class BankTransferTransaction extends Transaction
{
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Bank Transfer";
	}
	function displayPartner()
	{
	}
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
class ManualTransaction extends Transaction
{
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Manually Match A transaction";
	}
	function displayPartner()
	{
	}
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}
class SpecialCaseTransaction extends Transaction
{
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "";
	}
	function displayPartner()
	{
	}
	function selectAndDisplayButton()
	{
		$b = new AddNoButton( $this->id );
		$b->toHtml();
	}
}

