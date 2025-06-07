<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\LabelRowBase;
require_once( __DIR__ . '/../../HTML/HtmlElementInterface.php' );
require_once( __DIR__ . '/../../HTML/LabelRowBase.php' );

class DisplaySettledTransactions implements HtmlElementInterface
{
        protected $table;
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
        function toHtml()
        {
                $this->table->toHtml();
        }
        function getHtml()
        {
                $this->table->getHtml();
        }

}

class StatusSettled extends LabelRowBase
{
        function __construct()
        {
                $this->label = "Status:";
                $this->data = new HtmlBold( "Transaction Settled" );
                parent::__construct( "" );
        }
}
class UnsetTransaction extends LabelRowBase
{
        function __construct( $transaction )
        {
                $this->label = "Unset Transaction Association:";
                $this->data = submit( "UnsetTrans[$transaction->id]", _( "Unset Transaction $transaction->fa_trans_no"), false, '', 'default' );
                parent::__construct( "" );
        }
}
class SettledManual extends LabelRowBase
{
        function __construct()
        {
                $this->label = "Operation:";
                $this->data = "Manually Settled";
                parent::__construct( "" );
        }
}
class SettledDefault extends LabelRowBase
{
        function __construct( $transaction )
        {
                $this->label = "Status:";
                $this->data = "other transaction type; no info yet " . print_r( $transaction, true );
                parent::__construct( "" );
        }
}
abstract class SettledTable  implements HtmlElementInterface
{
        protected $table;
        function __construct( $transaction )
        {
                $this->table = $table = new HTML_TABLE( null, 100 );
                //$table->appendRow( new OperationPayment() );
        }
        function toHtml()
        {
                $this->table->toHtml();
        }
        function getHtml()
        {
                $this->table->getHtml();
        }
}

class SettledPaymentTable extends SettledTable
{
        protected $table;
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
class OperationPayment extends LabelRowBase
{
        function __construct()
        {
                $this->label = "Operation:";
                $this->data = "Payment";
                parent::__construct( "" );
        }
}
class FromBankAccount extends LabelRowBase
{
        function __construct( $transaction )
        {
                $this->label = "From Bank Account:";
                $this->data = $transaction->OurBankAccount;
                parent::__construct( "" );
        }
}
class SettledSupplier extends LabelRowBase
{
        function __construct( $transaction )
        {
                $this->label = "Supplier:";
                $this->data = $transaction->SupplierName;
                parent::__construct( "" );
        }
}
class SettledDepositTable extends SettledTable
{
        protected $table;
        function __construct( $transaction )
        {
		parent::__construct();
                $table->appendRow( new OperationDeposit() );
//TODO: Refactor to use fa_customer
		$custArray = get_customer_trans($transaction->fa_trans_no, $transaction->fa_trans_type);
                $table->appendRow( new CustomerBranch( $custArray ) );
        }
}
class OperationDeposit extends LabelRowBase
{
        function __construct()
        {
                $this->label = "Operation:";
                $this->data = "Deposit";
                parent::__construct( "" );
        }
}
class CustomerBranch extends LabelRowBase
{
        function __construct( $custArray )
        {
                $this->label = "Customer/Branch:";
                $this->data = get_customer_name($custArray['debtor_no']) . " / " . get_branch_name($custArray['branch_code']);
                parent::__construct( "" );
        }
}
