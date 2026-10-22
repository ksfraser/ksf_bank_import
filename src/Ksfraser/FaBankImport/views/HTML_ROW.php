<?php

use Ksfraser\HTML\Elements\HtmlTableRow;


/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class HTML_ROW implements HtmlElementInterface
{
	protected $row;
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $data )
	{
		$this->row = new HtmlTableRow( new HtmlString( $data ) );
	}
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function toHTML()
	{
		return $this->row->toHtml();
	}
}
