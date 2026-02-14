<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :AmountCharges [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for AmountCharges.
 */
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
