<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlEmptyElement extends HtmlElement
{
	function __construct( $data = "" )
	{
		parent::__construct( "" ); //Empty so no data passed in
		$this->empty = true;
	}
}
