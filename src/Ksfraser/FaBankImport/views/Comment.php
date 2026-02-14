<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :Comment [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for Comment.
 */
namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

class Comment implements HtmlElementInterface
{
	protected $row;
	function __construct( $bi_lineitem )
	{
	//label_row( (_("Comment:")), text_input( "comment_$this->id", $this->memo, strlen($this->memo), '', _("Comment:") )

		$data = text_input( "comment_$bi_lineitem->id", $bi_lineitem->memo, strlen($bi_lineitem->memo), '', _("Comment:") );
		$label = "Comment:";
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
