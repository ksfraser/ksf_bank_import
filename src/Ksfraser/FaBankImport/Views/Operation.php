<?php
 * Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlElementInterface;

class Operation implements HtmlElementInterface
{
	protected $row;
	function __construct( $bi_lineitem )
	{
		//label_row("Operation:", $this->oplabel, "width='25%' class='label'");
		$data = $bi_lineitem->oplabel";
		$label = "Operation:";
		$this->row = new HTML_ROW_LABEL( $data, $label,  null, null );
	}
	function getHtml()
	{
		$this->row->getHtml();
	}
	function toHtml()
	{
		$this->row->toHtml();
	}
}
