<?php

use Ksfraser\HTML\HtmlElementInterface;

require_once( 'LabelRowBase.php' );

class TransTitle extends LabelRowBase
{
	function __construct( $bi_lineitem )
	{
		$label = "Transaction Title:";
		$data =  $bi_lineitem->transactionTitle;
		parent::__construct( "" );
	}
}
