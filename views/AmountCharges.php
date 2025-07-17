<?php

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\LabelRowBase;

//require_once( 'LabelRowBase.php' );

class AmountCharges extends LabelRowBase
{
	function __construct( $bi_lineitem )
	{
		$label = "Amount / Charge(s):";
		$data =  $bi_lineitem->amount .' / ' . $bi_lineitem->charge . " (" . $bi_lineitem->currency .")";
		parent::__construct( "" );
	}
}
