<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlListItem extends HtmlElement
{
	//Held within either an Ordered List or Unordered List
	function __construct( $data )
	{
		if (!($data instanceof HtmlElementInterface)) {
			$data = new HtmlString((string)$data);
		}
		parent::__construct( $data );
		$this->tag = "li";
	}
}
