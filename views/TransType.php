<?php


//require_once( 'LabelRowBase.php' );
use Ksfraser\HTML\LabelRowBase;

class TransType extends LabelRowBase
{
	function __construct( $bi_lineitem )
	{
		switch( $bi_lineitem->transactionDC )
		{
			case 'C':
				$label = "Credit";
			break;
			case 'B':
				$label = "Bank Transfer";
			break;
			case 'D':
			default:
				$label = "Debit";
			break;
		}
                $this->label = "Trans Type:";
                $this->data = $label;
                parent::__construct( "" );
	}
}
