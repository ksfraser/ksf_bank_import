<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransDate [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransDate.
 */
namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

class TransDate implements HtmlElementInterface
{
	protected $row;
	function __construct( $bi_lineitem )
	{
		$data = $bi_lineitem->valueTimestamp . " :: (" . $bi_lineitem->entryTimestamp . ")";
		$label = "Trans Date (Event Date):";
		$this->row = new HTML_ROW_LABEL( $data, $label,  null, null );
	}
	function getHtml()
	{
		$this->row->getHtml();
	}
	function toHtml()
	{
		$this->row->toHtml();
	}
}
