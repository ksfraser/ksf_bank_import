<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlStrong extends HtmlFormatting
{
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "strong";
	}
}
