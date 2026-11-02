<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-LOG-007 | BR-LOG-002 | UAT-LOG-005
 */
class HtmlPreformatted extends HtmlElement
{
	/**
 * @BABOK FR-LOG-007 | BR-LOG-002 | UAT-LOG-005
	 */
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "pre";
	}
}
