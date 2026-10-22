<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK FR-PROC-001, FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 */

/**
 * @BABOK FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
 */

class FromBankAccount extends LabelRowBase
{
        /**
 * @BABOK FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct( $transaction )
        {
                $this->label = "From Bank Account:";
                $this->data = $transaction->OurBankAccount;
                parent::__construct( "" );
        }
}
/**
 * @BABOK FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
 */
