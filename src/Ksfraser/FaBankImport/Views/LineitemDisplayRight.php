<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/LineitemDisplayRight.php; coverage: docs/TestCases/ (file rollup)
 */
class LineitemDisplayRight implements HtmlElementInterface
{
        protected $table;
        /** @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct */
        function __construct( $bi_lineitem )
        {
                $this->table = $table = new HTML_TABLE( null, 100 );
                $table->appendRow( new Operation( $bi_lineitem ) );
                $table->appendRow( new PartnerType( $bi_lineitem ) );
                $table->appendRow( new PartnerSubSelect( $bi_lineitem ) );
                $table->appendRow( new Comment( $bi_lineitem ) );
                $table->appendRow( new MatchingGLS( $bi_lineitem ) );
        }
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: toHtml */
	function toHtml()
	{
		$this->table->toHtml();
	}
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml */
	function getHtml()
	{
		$this->table->getHtml();
	}
}

