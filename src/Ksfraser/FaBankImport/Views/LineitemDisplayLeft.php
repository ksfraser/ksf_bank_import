<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

use Ksfraser\HTML\Composites\HTML_TABLE;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class LineitemDisplayLeft implements HtmlElementInterface
{
        protected $table;
        /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct( $bi_lineitem )
        {
                $this->table = $table = new HTML_TABLE( null, 100 );
                $table->appendRow( new TransDate( $bi_lineitem ) );
                $table->appendRow( new TransType( $bi_lineitem ) );
                $table->appendRow( new OurBankAccount( $bi_lineitem ) );
                $table->appendRow( new OtherBankAccount( $bi_lineitem ) );
                $table->appendRow( new AmountCharges( $bi_lineitem ) );
                $table->appendRow( new TransTitle( $bi_lineitem ) );
        }
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function toHtml(): void
	{
		$this->table->toHtml();
	}
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function getHtml(): string
	{
		return $this->table->getHtml();
	}
}

