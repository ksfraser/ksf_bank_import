<?php

use Ksfraser\HTML\HtmlElementInterface;

class HTML_ROW_LABELDecorator implements HtmlElementInterface
{
	protected $HTML_LABEL_ROW;
	function __construct( $data, $label, $width = 25, $class = 'label' )
	{
		$this->HTML_LABEL_ROW = new HTML_ROW_LABEL( $data, $label, $width, $class );
	}
	function toHTML()
	{
		return $this->HTML_LABEL_ROW->toHTML();
	}
	function getHTML()
	{
		return $this->HTML_LABEL_ROW->getHTML();
	}
}
