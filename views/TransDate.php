<?php

use Ksfraser\HTML\HtmlElementInterface;

class TransDate implements HtmlElementInterface
{
	protected $row;
	function __construct( $bi_lineitem )
	{
		$this->row = new HTML_ROW_LABEL( "Trans Date (Event Date):", $this->bi_lineitem->valueTimestamp . " :: (" . $this->bi_lineitem->entryTimestamp . ")",  null, null );
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
