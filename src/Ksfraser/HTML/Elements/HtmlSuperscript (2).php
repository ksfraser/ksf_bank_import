<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Formatting\HtmlFormatting;

class HtmlSuperscript extends HtmlFormatting
{
	       function __construct( HtmlElementInterface $data )
	       {
		       parent::__construct( $data );
		       $this->tag = "sup";
	       }
}
