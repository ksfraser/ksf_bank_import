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

class CustomerBranch extends LabelRowBase
{
        /**
 * @BABOK FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct( $custArray )
        {
                $this->label = "Customer/Branch:";
                $this->data = get_customer_name($custArray['debtor_no']) . " / " . get_branch_name($custArray['branch_code']);
                parent::__construct( "" );
        }
}
