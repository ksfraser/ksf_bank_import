<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK CLASS ROLLUP: PartnerType — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/PartnerType.php; coverage: docs/TestCases/ (file rollup)
 */
class PartnerType implements HtmlElementInterface
{
	protected $row;
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct */
	function __construct( $bi_lineitem )
	{
		// label_row("Partner:", array_selector("partnerType[$this->id]", $_POST['partnerType'][$this->id], $this->optypes, array('select_submit'=> true)));
		$data = array_selector("partnerType[$bi_lineitem->id]", $_POST['partnerType'][$bi_lineitem->id], $bi_lineitem->optypes, array('params' => 'onchange="preserveSubmit(this)"') );
		$label = "Partner";
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
