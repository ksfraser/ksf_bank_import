<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-VAL-004, FR-IMP-001 | BR-VAL-004 | UAT-VAL-006
 */
class HtmlListItem extends HtmlElement
{
	//Held within either an Ordered List or Unordered List
	/**
 * @BABOK FR-VAL-004, FR-IMP-001 | BR-VAL-004 | UAT-VAL-006
	 */
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "li";
	}
}
