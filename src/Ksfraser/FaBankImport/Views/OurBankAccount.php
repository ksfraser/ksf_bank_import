<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
 */
class OurBankAccount extends LabelRowBase
{
	/**
 * @BABOK FR-PROC-003 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $bi_lineitem )
	{
		$this->label = "Our Bank Account - (Account Name)(Number):";
		$this->data = $bi_lineitem->our_account . ' - ' . $bi_lineitem->ourBankDetails['bank_name'] . " (" . $bi_lineitem->ourBankAccountName . ")(" . $bi_lineitem->ourBankAccountCode . ")";
                parent::__construct( "" );
	}
}

