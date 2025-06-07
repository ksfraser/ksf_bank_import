<?php

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;

class PartnerType implements HtmlElementInterface
{
	protected $row;
	function __construct( $bi_lineitem )
	{
		// label_row("Partner:", array_selector("partnerType[$this->id]", $_POST['partnerType'][$this->id], $this->optypes, array('select_submit'=> true)));
		$data = array_selector("partnerType[$bi_lineitem->id]", $_POST['partnerType'][$bi_lineitem->id], $bi_lineitem->optypes, array('select_submit'=> true) );
		$label = "Partner";
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
