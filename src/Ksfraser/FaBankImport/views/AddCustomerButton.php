<?php

use Ksfraser\HTML\Composites\HTML_LABEL_ROW;
use Ksfraser\HTML\Composites\HTML_ROW_LABELDecorator;

namespace Ksfraser\FaBankImport;

//TODO: Refactor to replace the Submit button with our own class.

class AddCustomerButton
{
	protected $HTML_LABEL_ROW;
	function __construct( int $index )
	{
		$data = submit( "AddCustomer[$index]", _("AddCustomer"), false, '', 'default' ) ;
		$label = "Add Customer" ;
		$this->HTML_LABEL_ROW = new HTML_ROW_LABELDecorator( $data, $label );
		//label_row("Add Customer", submit("AddCustomer[$this->id]",_("AddCustomer"),false, '', 'default'));
	}
	function toHTML()
	{
		$this->HTML_LABEL_ROW->toHTML();
	}
}
