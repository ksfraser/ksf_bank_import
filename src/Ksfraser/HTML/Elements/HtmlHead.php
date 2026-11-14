<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-IMP-001, FR-UPL-001 | BR-IMP-001 | UAT-IMP-001
 */
class HtmlHead extends HtmlElement
{
	//can have styles
	/**
 * @BABOK FR-IMP-001, FR-UPL-001 | BR-IMP-001 | UAT-IMP-001
	 */
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "head";
	}
}
