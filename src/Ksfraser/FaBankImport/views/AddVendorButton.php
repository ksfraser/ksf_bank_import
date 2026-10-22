<?php

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\Composites\HTML_LABEL_ROW;
use Ksfraser\HTML\Composites\HTML_ROW_LABELDecorator;
require_once( __DIR__ . "/HTML/HTML_ROW_LABELDecorator.php" );



//TODO: Refactor to replace the Submit button with our own class.

/**
 * @BABOK FR-PROC-013 | BR-PROC-006
 */
class AddVendorButton 
{
	protected $HTML_LABEL_ROW;
	/**
 * @BABOK FR-PROC-013 | BR-PROC-006
	 */
	function __construct( int $index )
	{
		$data = submit( "AddVendor[$index]", _("AddVendor"), false, '', 'default' );
		$label =  "Add Vendor" ;
		$this->HTML_LABEL_ROW = new HTML_ROW_LABELDecorator(  $data, $label );
		//label_row("Add Vendor", submit("AddVendor[$this->id]",_("AddVendor"),false, '', 'default'));
	}
	/**
 * @BABOK FR-PROC-013 | BR-PROC-006
	 */
	function toHTML()
	{
		$this->HTML_LABEL_ROW->toHTML();
	}
}
