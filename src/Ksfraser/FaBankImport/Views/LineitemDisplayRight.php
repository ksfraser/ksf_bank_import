<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;


use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_TABLE;

/**
 * @BABOK FR-PROC-001, FR-PROC-002, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001

 */
class LineitemDisplayRight implements HtmlElementInterface
{
        protected $table;
        /** * @BABOK FR-PROC-001, FR-PROC-002, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
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
	/** * @BABOK FR-PROC-001, FR-PROC-002, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001, FR-PROC-002, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
*/
	function toHtml(): void
	{
		$this->table->toHtml();
	}
	/** * @BABOK FR-PROC-001, FR-PROC-002, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001, FR-PROC-002, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
*/
	function getHtml(): string
	{
				return $this->table->getHtml();
		}
}

