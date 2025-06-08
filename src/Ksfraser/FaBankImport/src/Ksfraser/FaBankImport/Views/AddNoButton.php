<?php

use Ksfraser\HTML\HTML_LABEL_ROW;
use Ksfraser\HTML\HTML_ROW_LABELDecorator;

namespace Ksfraser\FaBankImport;


//TODO: Refactor to replace the Submit button with our own class.

class AddNoButton
{
	protected $HTML_LABEL_ROW;
	function __construct( int $index )
	{
		$data = new HtmlString( "There is nothing to add" );
		$label = "Add Button";
		$this->HTML_LABEL_ROW = new HTML_ROW_LABEL( $data, $label );
	}
	function toHTML()
	{
		$this->HTML_LABEL_ROW->toHTML();
	}
}
