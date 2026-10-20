<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: BR-009 / ARCH-009 / FR-009*/

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK CLASS ROLLUP: Comment — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/Comment.php; coverage: docs/TestCases/ (file rollup)
 */
class Comment implements HtmlElementInterface
{
	protected $row;
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct */
	function __construct( $bi_lineitem )
	{
	//label_row( (_("Comment:")), text_input( "comment_$this->id", $this->memo, strlen($this->memo), '', _("Comment:") )

		$data = text_input( "comment_$bi_lineitem->id", $bi_lineitem->memo, strlen($bi_lineitem->memo), '', _("Comment:") );
		$label = "Comment:";
		$this->row = new HTML_ROW_LABEL( $data, $label,  null, null );
	}
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml */
	function getHtml()
	{
		$this->row->getHtml();
	}
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: toHtml */
	function toHtml()
	{
		$this->row->toHtml();
	}
}
