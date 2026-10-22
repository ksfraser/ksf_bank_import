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

class SettledDepositTable extends SettledTable
{
        protected $table;
        /**
 * @BABOK FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct( $transaction )
        {
		parent::__construct();
                $table->appendRow( new OperationDeposit() );
//TODO: Refactor to use fa_customer
		$custArray = get_customer_trans($transaction->fa_trans_no, $transaction->fa_trans_type);
                $table->appendRow( new CustomerBranch( $custArray ) );
        }
}
/**
 * @BABOK FR-PROC-005 | BR-PROC-001 | UAT-PROC-001
 */
