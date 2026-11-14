<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;


use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001

 */
class PartnerType implements HtmlElementInterface
{
	protected $row;
	/** * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
	function __construct( $bi_lineitem )
	{
		// label_row("Partner:", array_selector("partnerType[$this->id]", $_POST['partnerType'][$this->id], $this->optypes, array('select_submit'=> true)));
		$data = array_selector("partnerType[$bi_lineitem->id]", $_POST['partnerType'][$bi_lineitem->id], $bi_lineitem->optypes, array('params' => 'onchange="preserveSubmit(this)"') );
		$label = "Partner";
		$this->row = new HTML_ROW_LABEL( $data, $label,  null, null );
	}
	/** * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
	function getHtml(): string
	{
				return $this->row->getHtml();
		}
	/** * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
	function toHtml(): void
	{
		$this->row->toHtml();
	}
}
