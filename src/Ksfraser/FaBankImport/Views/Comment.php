<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: BR-009 / ARCH-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;


use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001

 */
class Comment implements HtmlElementInterface
{
	protected $row;
	/** * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
*/
	function __construct( $bi_lineitem )
	{
	//label_row( (_("Comment:")), text_input( "comment_$this->id", $this->memo, strlen($this->memo), '', _("Comment:") )

		$data = text_input( "comment_$bi_lineitem->id", $bi_lineitem->memo, strlen($bi_lineitem->memo), '', _("Comment:") );
		$label = "Comment:";
		$this->row = new HTML_ROW_LABEL( $data, $label,  null, null );
	}
	/** * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
*/
	function getHtml(): string
	{
				return $this->row->getHtml();
		}
	/** * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
*/
	function toHtml(): void
	{
		$this->row->toHtml();
	}
}
