<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-VAL-004, FR-IMP-001 | BR-VAL-004 | UAT-IMP-001
 */
class HtmlUnorderedList extends HtmlElement
{
	//can have styles
	/**
 * @BABOK FR-VAL-004, FR-IMP-001 | BR-VAL-004 | UAT-IMP-001
	 */
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "ul";
	}
}
