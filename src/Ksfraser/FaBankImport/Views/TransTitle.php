<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class TransTitle extends LabelRowBase
{
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $bi_lineitem )
	{
		$this->label = "Transaction Title:";
		$this->data =  $bi_lineitem->transactionTitle;
		parent::__construct( "" );
	}
}
