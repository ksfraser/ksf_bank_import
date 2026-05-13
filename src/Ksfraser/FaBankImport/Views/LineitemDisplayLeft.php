<?php

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

class LineitemDisplayLeft implements HtmlElementInterface
{
        protected $table;
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
	function toHtml()
	{
		$this->table->toHtml();
	}
	function getHtml()
	{
		$this->table->getHtml();
	}
}

