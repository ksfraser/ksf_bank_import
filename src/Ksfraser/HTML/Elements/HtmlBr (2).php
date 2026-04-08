<?php
namespace Ksfraser\HTML\Elements;

// TODO: Create English human-readable wrappers for tags such as HtmlBreak (wraps HtmlBr), HtmlBold (wraps HtmlB), HtmlItalics (wraps HtmlI), etc. to improve semantic clarity and usability for coders unfamiliar with raw HTML tag names.

use Ksfraser\HTML\HtmlEmptyElement;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlBr extends HtmlEmptyElement
{
	function __construct( $data = "" )
	{
		parent::__construct( "" );
		$this->tag = "br";
	}
}
