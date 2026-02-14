<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :HTML_ROW [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for HTML_ROW.
 */
use Ksfraser\HTML\Elements\HtmlTableRow;


class HTML_ROW implements HtmlElementInterface
{
	protected $row;
	function __construct( $data )
	{
		$this->row = new HtmlTableRow( new HtmlString( $data ) );
	}
	function toHTML()
	{
		return $this->row->toHtml();
	}
}
