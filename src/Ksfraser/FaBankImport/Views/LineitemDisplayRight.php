<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK CLASS ROLLUP: LineitemDisplayRight — 3 methods; FR-009 (3); IR-001 (3)
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
	function toHtml(): void
	{
		$this->table->toHtml();
	}
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml */
	function getHtml(): string
	{
				return $this->table->getHtml();
		}
}

