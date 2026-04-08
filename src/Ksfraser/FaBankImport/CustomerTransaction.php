<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Transaction;
use Ksfraser\FaBankImport\AddCustomerButton;

class CustomerTransaction extends Transaction
{
	function __construct( array $trz )
	{
		parent::__construct( $trz );
		$this->oplabel = "Customer Payment";
	}
	function displayPartner()
	{
	}
	function selectAndDisplayButton()
	{
		$b = new AddCustomerButton( $this->id );
		$b->toHtml();
	}
}
