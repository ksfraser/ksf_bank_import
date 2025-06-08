<?php

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

require_once( 'LabelRowBase.php' );

class TransTitle extends LabelRowBase
{
	function __construct( $bi_lineitem )
	{
		$this->label = "Transaction Title:";
		$this->data =  $bi_lineitem->transactionTitle;
		parent::__construct( "" );
	}
}
