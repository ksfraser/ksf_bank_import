<?php

namespace Ksfraser\FaBankImport;


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
	function getHtml()
	{
		$this->row->getHtml();
	}
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function toHtml()
	{
		$this->row->toHtml();
	}
}
