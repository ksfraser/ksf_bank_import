<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HTML_LABEL_ROW;
use Ksfraser\HTML\Composites\HTML_ROW_LABELDecorator;

//TODO: Refactor to replace the Submit button with our own class.

/**
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
 */
class AddCustomerButton
{
	protected $HTML_LABEL_ROW;
	/**
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
	 */
	function __construct( int $index )
	{
		$data = submit( "AddCustomer[$index]", _("AddCustomer"), false, '', 'default' ) ;
		$label = "Add Customer" ;
		$this->HTML_LABEL_ROW = new HTML_ROW_LABELDecorator( $data, $label );
		//label_row("Add Customer", submit("AddCustomer[$this->id]",_("AddCustomer"),false, '', 'default'));
	}
	/**
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
	 */
	function toHTML()
	{
		$this->HTML_LABEL_ROW->toHTML();
	}
}
