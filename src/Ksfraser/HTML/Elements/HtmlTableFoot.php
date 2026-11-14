<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-001, FR-UPL-001, FR-INQ-003 | BR-PROC-001 | UAT-PROC-001
 */
class HtmlTableFoot extends HtmlElement
{
	//can have styles
	/**
 * @BABOK FR-PROC-001, FR-UPL-001, FR-INQ-003 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "tfoot";
	}
}
