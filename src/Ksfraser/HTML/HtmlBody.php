<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlBody extends HtmlElement
{
	//can have styles
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "body";
	}
}
