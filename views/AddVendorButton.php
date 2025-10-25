<?php

use Ksfraser\HTML\Composites\HTML_LABEL_ROW;

//TODO: Refactor to replace the Submit button with our own class.

class AddVendorButton 
{
	protected $HTML_LABEL_ROW;
	function __construct( int $index )
	{
		$this->HTML_LABEL_ROW = new HTML_ROW_LABELDecorator( 
						submit( "AddVendor[$index]", _("AddVendor"), false, '', 'default' ), 
						"Add Vendor" );
		//label_row("Add Vendor", submit("AddVendor[$this->id]",_("AddVendor"),false, '', 'default'));
	}
	function toHTML()
	{
		$this->HTML_LABEL_ROW->toHTML();
	}
}
