<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

class LineitemDisplayRight implements HtmlElementInterface
{
        protected $table;
        function __construct( $bi_lineitem )
        {
                $this->table = $table = new HTML_TABLE( null, 100 );
                $table->appendRow( new Operation( $bi_lineitem ) );
                $table->appendRow( new PartnerType( $bi_lineitem ) );
                $table->appendRow( new PartnerSubSelect( $bi_lineitem ) );
                $table->appendRow( new Comment( $bi_lineitem ) );
                $table->appendRow( new MatchingGLS( $bi_lineitem ) );
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

