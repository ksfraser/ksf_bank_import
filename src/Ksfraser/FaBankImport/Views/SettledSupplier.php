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

class SettledSupplier extends LabelRowBase
{
        /**
 * @BABOK FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct( $transaction )
        {
                $this->label = "Supplier:";
                $this->data = $transaction->SupplierName;
                parent::__construct( "" );
        }
}
/**
 * @BABOK FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
 */
