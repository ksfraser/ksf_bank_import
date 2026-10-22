<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-IMP-001, FR-UPL-001 | BR-IMP-001 | UAT-IMP-001
 */
class HtmlTitle extends HtmlElement
{
	//can have styles
	//Only belongs in the HEAD
	/**
 * @BABOK FR-IMP-001, FR-UPL-001 | BR-IMP-001 | UAT-IMP-001
	 */
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "title";
	}
}
