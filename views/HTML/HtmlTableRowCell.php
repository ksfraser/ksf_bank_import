<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Elements\HtmlElement;

class HtmlTableRowCell extends HtmlElement
{
	//can have styles
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "td";
	}
}
