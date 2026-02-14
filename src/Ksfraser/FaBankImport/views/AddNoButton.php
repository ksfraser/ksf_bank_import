<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :AddNoButton [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for AddNoButton.
 */
namespace Ksfraser\FaBankImport;

use Ksfraser\HTML\Composites\HTML_LABEL_ROW;
use Ksfraser\HTML\Composites\HTML_ROW_LABELDecorator;


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
