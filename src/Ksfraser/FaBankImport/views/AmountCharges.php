<?php

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

require_once( 'LabelRowBase.php' );

class AmountCharges extends LabelRowBase
{
	function __construct( $bi_lineitem )
	{
		$this->label = "Amount / Charge(s):";
		$this->data =  $bi_lineitem->amount .' / ' . $bi_lineitem->charge . " (" . $bi_lineitem->currency .")";
		parent::__construct( "" );
	}
}
