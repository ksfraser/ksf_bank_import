<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HTML_ROW_LABEL;


class HTML_ROW_LABELDecorator implements HtmlElementInterface
{
	protected $HTML_LABEL_ROW;

	function __construct( $data, $label, $width = 25, $class = 'label' )
	{
		$this->HTML_LABEL_ROW = new HTML_ROW_LABEL( $data, $label, $width, $class );
	}
	public function toHtml():void
	{
		//return $this->HTML_LABEL_ROW->toHtml();
		//Claude says no return...
		$this->HTML_LABEL_ROW->toHtml();
	}
	public function getHtml():bool|string
	{
		return $this->HTML_LABEL_ROW->getHtml();
	}
}
