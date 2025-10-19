<?php

use Ksfraser\HTML\HtmlTableRow;


class HTML_ROW implements HtmlElementInterface
{
	protected $row;
	function __construct( $data )
	{
		$this->row = new HtmlTableRow( new HtmlString( $data ) );
	}
	function toHTML()
	{
		return $this->row->toHtml();
	}
}
