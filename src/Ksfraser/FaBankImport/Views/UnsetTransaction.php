<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\LabelRowBase;

/**
 * @BABOK FR-PROC-001, FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 */

/**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010
 */

class UnsetTransaction extends LabelRowBase
{
        /**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010
         */
        function __construct( $transaction )
        {
                $this->label = "Unset Transaction Association:";
                $this->data = submit( "UnsetTrans[$transaction->id]", _( "Unset Transaction $transaction->fa_trans_no"), false, '', 'default' );
                parent::__construct( "" );
        }
}
/**
 * @BABOK FR-PROC-008 | BR-PROC-002 | UAT-PROC-008
 */
