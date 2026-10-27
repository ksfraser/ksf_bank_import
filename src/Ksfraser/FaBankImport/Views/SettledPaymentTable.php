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

class SettledPaymentTable extends SettledTable
{
        protected $table;
        /**
 * @BABOK FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct( $transaction )
        {
//TODO data was passed in in an minfo array
//	Ensure the data is available in transaction.
		parent::__construct( $transaction );
		$this->table->appendRow( new OperationPayment() );
		$this->table->appendRow( new FromBankAccount( $transaction ) );
		$this->table->appendRow( new SettledSupplier( $transaction ) );
        }
}
/**
 * @BABOK FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
 */
