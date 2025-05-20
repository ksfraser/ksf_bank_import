<?php

use Ksfraser\HTML\HtmlElementInterface;

require_once( 'LabelRowBase.php' );

class OurBankAccount extends LabelRowBase
{
	function __construct( $bi_lineitem )
	{
		$this->label = "Our Bank Account - (Account Name)(Number):";
		$this->data = $bi_lineitem->our_account . ' - ' . $bi_lineitem->ourBankDetails['bank_name'] . " (" . $bi_lineitem->ourBankAccountName . ")(" . $bi_lineitem->ourBankAccountCode . ")";
                parent::__construct( "" );
	}
}


