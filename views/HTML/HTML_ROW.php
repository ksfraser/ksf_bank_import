<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\Elements\HtmlTableRow;
use Ksfraser\HTML\Elements\HtmlString;



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
