<?php

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-001, FR-PROC-002, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
 */
class LineitemDisplayRight implements HtmlElementInterface
{
        protected $table;
        /**
 * @BABOK FR-PROC-001, FR-PROC-002, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct( $bi_lineitem )
        {
                $this->table = $table = new HTML_TABLE( null, 100 );
                $table->appendRow( new Operation( $bi_lineitem ) );
                $table->appendRow( new PartnerType( $bi_lineitem ) );
                $table->appendRow( new PartnerSubSelect( $bi_lineitem ) );
                $table->appendRow( new Comment( $bi_lineitem ) );
                $table->appendRow( new MatchingGLS( $bi_lineitem ) );
        }
	/**
 * @BABOK FR-PROC-001, FR-PROC-002, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
	 */
	function toHtml()
	{
		$this->table->toHtml();
	}
	/**
 * @BABOK FR-PROC-001, FR-PROC-002, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
	 */
	function getHtml()
	{
		$this->table->getHtml();
	}
}

