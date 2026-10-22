<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\LabelRowBase;
use Ksfraser\HTML\Composites\HTML_TABLE;

/**
 * @BABOK FR-PROC-001, FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 */

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
        function toHtml(): void
        {
                $this->table->toHtml();
        }
        /**
 * @BABOK FR-PROC-001, FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
         */
        function getHtml(): string
        {
                return $this->table->getHtml();
        }

}

/**
 * @BABOK FR-PROC-001, FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
 */
