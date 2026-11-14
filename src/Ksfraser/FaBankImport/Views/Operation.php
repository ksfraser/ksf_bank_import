<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;


use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class Operation implements HtmlElementInterface
{
	protected $row;
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $bi_lineitem )
	{
		//label_row("Operation:", $this->oplabel, "width='25%' class='label'");
		$data = $bi_lineitem->oplabel;
		$label = "Operation:";
		$this->row = new HTML_ROW_LABEL( $data, $label,  null, null );
	}
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function getHtml(): string
	{
		return $this->row->getHtml();
	}
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function toHtml(): void
	{
		$this->row->toHtml();
	}
}
