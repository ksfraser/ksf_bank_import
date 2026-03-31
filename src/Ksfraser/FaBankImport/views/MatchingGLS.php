<?php

namespace Ksfraser\FaBankImport\Views;


use Ksfraser\HTML\HtmlElementInterface;

class MatchingGLS implements HtmlElementInterface
{
	protected $row;
	function __construct( $bi_lineitem )
	{
		$data = new MatchingGLFactory( $bi_lineitem );
		$label = "Matching GLs:";
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

class MatchingGLFactory
{
	function __construct( $bi_lineitem )
	{
	}
}



