<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK FR-PROC-001, FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 */

/**
 * @BABOK FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
 */

class OperationDeposit extends LabelRowBase
{
        /**
 * @BABOK FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct()
        {
                $this->label = "Operation:";
                $this->data = "Deposit";
                parent::__construct( "" );
        }
}
/**
 * @BABOK FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
 */
