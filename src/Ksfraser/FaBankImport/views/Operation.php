<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :Operation [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for Operation.
 */
namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

class Operation implements HtmlElementInterface
{
	protected $row;
	function __construct( $bi_lineitem )
	{
		//label_row("Operation:", $this->oplabel, "width='25%' class='label'");
		$data = $bi_lineitem->oplabel;
		$label = "Operation:";
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
