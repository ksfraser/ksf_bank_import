<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Ksfraser\HTML\Elements\HtmlString;

//TODO: Refactor to replace the Submit button with our own class.

class AddNoButton implements \Ksfraser\HTML\HtmlElementInterface
{
	protected $HTML_LABEL_ROW;
	function __construct( int $index )
	{
		$data = new HtmlString( "There is nothing to add" );
		$label = "Add Button";
		$this->HTML_LABEL_ROW = new HTML_ROW_LABEL( $data, $label );
	}
	function toHtml(): void
	{
		$this->HTML_LABEL_ROW->toHtml();
	}
	function getHtml(): string
	{
		return $this->HTML_LABEL_ROW->getHtml();
	}
}
