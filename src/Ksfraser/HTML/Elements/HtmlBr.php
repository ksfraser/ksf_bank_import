<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlEmptyElement;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-IMP-010, FR-PROC-009 | BR-IMP-001 | UAT-IMP-001
 */
class HtmlBr extends HtmlEmptyElement
{
	/**
 * @BABOK FR-IMP-010, FR-PROC-009 | BR-IMP-001 | UAT-IMP-001
	 */
	function __construct( $data = "" )
	{
		parent::__construct( "" );
		$this->tag = "br";
	}
}
