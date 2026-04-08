<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlEmptyElement;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlHr extends HtmlEmptyElement
{
	       function __construct( HtmlElementInterface $data )
	       {
		       parent::__construct( $data );
		       $this->tag = "hr";
	       }
}
