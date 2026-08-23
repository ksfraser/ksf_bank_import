<?php

use Ksfraser\HTML\Composites\HTML_LABEL_ROW;
use Ksfraser\HTML\Elements\HtmlRaw;

//TODO: Refactor to replace the Submit button with our own class.

class AddCustomerButton
{
	protected $HTML_LABEL_ROW;
	function __construct( int $index )
	{
		$this->HTML_LABEL_ROW = new HTML_ROW_LABELDecorator(
						new HtmlRaw( submit( "AddCustomer[$index]", _("AddCustomer"), false, '', 'default' ) ),
						"Add Customer" );
		//label_row("Add Customer", submit("AddCustomer[$this->id]",_("AddCustomer"),false, '', 'default'));
	}
	function toHTML()
	{
		$this->HTML_LABEL_ROW->toHTML();
	}
}
