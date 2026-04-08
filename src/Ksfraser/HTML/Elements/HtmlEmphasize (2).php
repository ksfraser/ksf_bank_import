<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Formatting\HtmlFormatting;

class HtmlEmphasize extends HtmlFormatting
{
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "em";
	}
}
