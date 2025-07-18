<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlTableCaption extends HtmlElement
{
	//After <table> but before rows
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "caption";
	}
}
