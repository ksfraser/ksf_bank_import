<?php

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

require_once( 'LabelRowBase.php' );

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class OtherBankAccount extends LabelRowBase
{
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $bi_lineitem )
	{
		$this->label = "Other Bank Account:";
		$this->data = $bi_lineitem->otherBankAccount . ' / '. $bi_lineitem->otherBankAccountName;
		parent::__construct( "" );
	}
}
