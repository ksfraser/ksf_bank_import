<?php

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\LabelRowBase;

//require_once( 'LabelRowBase.php' );

class OtherBankAccount extends LabelRowBase
{
	function __construct( $bi_lineitem )
	{
		$this->label = "Other Bank Account:";
		$this->data = $bi_lineitem->otherBankAccount . ' / '. $bi_lineitem->otherBankAccountName;
		parent::__construct( "" );
	}
}
