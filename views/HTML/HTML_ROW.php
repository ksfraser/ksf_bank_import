<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlTableRow;
use Ksfraser\HTML\HtmlString;



class HTML_ROW implements HtmlElementInterface
{
	protected $row;
	function __construct( $data )
	{
		$this->row = new HtmlTableRow( new HtmlString( $data ) );
	}
	function toHTML():void
	{
		$this->row->toHtml();
		//return $this->row->toHtml();
	}
	function getHTML():string
	{
		return $this->row->getHtml();
	}
}
