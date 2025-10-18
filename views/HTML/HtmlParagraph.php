<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlParagraph extends HtmlElement
{
	//can have styles
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "p";
	}
}
