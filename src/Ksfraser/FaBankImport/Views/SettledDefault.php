<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK FR-PROC-001, FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 */

/**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
 */

class SettledDefault extends LabelRowBase
{
        /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct( $transaction )
        {
                $this->label = "Status:";
                $this->data = "other transaction type; no info yet " . print_r( $transaction, true );
                parent::__construct( "" );
        }
}
/**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
 */
