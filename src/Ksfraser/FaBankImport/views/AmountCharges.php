<?php

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

require_once( 'LabelRowBase.php' );

/**
 * @BABOK FR-PROC-001, FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
 */
class AmountCharges extends LabelRowBase
{
	/**
 * @BABOK FR-PROC-001, FR-PROC-006 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $bi_lineitem )
	{
		$this->label = "Amount / Charge(s):";
		$this->data =  $bi_lineitem->amount .' / ' . $bi_lineitem->charge . " (" . $bi_lineitem->currency .")";
		parent::__construct( "" );
	}
}
