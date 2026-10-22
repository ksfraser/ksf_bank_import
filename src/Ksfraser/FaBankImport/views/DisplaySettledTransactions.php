<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\LabelRowBase;
require_once( __DIR__ . '/../../HTML/HtmlElementInterface.php' );
require_once( __DIR__ . '/../../HTML/LabelRowBase.php' );

/**
 * @BABOK FR-PROC-001, FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 */
class DisplaySettledTransactions implements HtmlElementInterface
{
        protected $table;
	/**
 * @BABOK FR-PROC-001, FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $transaction )
	{
                $this->table = $table = new HTML_TABLE( null, 100 );
                $table->appendRow( new StatusSettled() );

                switch ($transaction->fa_trans_type)
                {
                        case ST_SUPPAYMENT:
				$table->appendRow( new SettledPaymentTable );
                        break;
                        case ST_BANKDEPOSIT:
				$table->appendRow( new SettledDepositTable );
                        break;
                        case 0:
				$table->appendRow( new SettledManual() );
                        break;
                        default:
				$table->appendRow( new SettledDefault() );
                        break;
                }
		$table->appendRow( new UnsetTransaction( $transaction ) );
                //label_row( "Unset Transaction Association", submit( "UnsetTrans[$this->id]", _( "Unset Transaction $this->fa_trans_no"), false, '', 'default' ));
	}
        /**
 * @BABOK FR-PROC-001, FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
         */
        function toHtml()
        {
                $this->table->toHtml();
        }
        /**
 * @BABOK FR-PROC-001, FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
         */
        function getHtml()
        {
                $this->table->getHtml();
        }

}

/**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
 */
class StatusSettled extends LabelRowBase
{
        /**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct()
        {
                $this->label = "Status:";
                $this->data = new HtmlBold( "Transaction Settled" );
                parent::__construct( "" );
        }
}
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
class SettledManual extends LabelRowBase
{
        /**
 * @BABOK FR-PROC-008 | BR-PROC-002 | UAT-PROC-008
         */
        function __construct()
        {
                $this->label = "Operation:";
                $this->data = "Manually Settled";
                parent::__construct( "" );
        }
}
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
abstract class SettledTable  implements HtmlElementInterface
{
        protected $table;
        /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct( $transaction )
        {
                $this->table = $table = new HTML_TABLE( null, 100 );
                //$table->appendRow( new OperationPayment() );
        }
        /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
         */
        function toHtml()
        {
                $this->table->toHtml();
        }
        /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
         */
        function getHtml()
        {
                $this->table->getHtml();
        }
}

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
		parent::__construct();
                $table->appendRow( new OperationPayment() );
                $table->appendRow( new FromBankAccount( $transaction ) );
                $table->appendRow( new SettledSupplier( $transaction ) );
        }
}
/**
 * @BABOK FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
 */
class OperationPayment extends LabelRowBase
{
        /**
 * @BABOK FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct()
        {
                $this->label = "Operation:";
                $this->data = "Payment";
                parent::__construct( "" );
        }
}
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
