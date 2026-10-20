<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlElementInterface;

/**
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
