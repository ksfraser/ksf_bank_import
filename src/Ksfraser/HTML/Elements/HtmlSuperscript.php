<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-IMP-010, FR-PROC-009 | BR-IMP-001 | UAT-IMP-001
 */
class HtmlSuperscript extends HtmlFormatting
{
	/**
 * @BABOK FR-IMP-010, FR-PROC-009 | BR-IMP-001 | UAT-IMP-001
	 */
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "sup";
	}
}
