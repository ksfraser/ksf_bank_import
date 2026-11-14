<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-INQ-003, FR-PROC-002 | BR-INQ-001 | UAT-INQ-001
 */
class HtmlDescriptionTerm extends HtmlElement
{
	//can have styles
	/**
 * @BABOK FR-INQ-003, FR-PROC-002 | BR-INQ-001 | UAT-INQ-001
	 */
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "dt";
	}
}
