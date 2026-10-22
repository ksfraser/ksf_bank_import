<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class TransDate implements HtmlElementInterface
{
	protected $row;
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $bi_lineitem )
	{
		$data = $bi_lineitem->valueTimestamp . " :: (" . $bi_lineitem->entryTimestamp . ")";
		$label = "Trans Date (Event Date):";
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
