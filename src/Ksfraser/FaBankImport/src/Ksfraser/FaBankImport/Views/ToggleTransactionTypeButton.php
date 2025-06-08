<?php

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HTML_LABEL_ROW;
use Ksfraser\HTML\HTML_ROW_LABELDecorator;
require_once( __DIR__ . "/HTML/HTML_ROW_LABELDecorator.php" );



//TODO: Refactor to replace the Submit button with our own class.

class ToggleTransactionTypeButton
{
	protected $HTML_LABEL_ROW;
	function __construct( int $index )
	{
		$data = submit("ToggleTransaction[$index]",_("ToggleTransaction"),false, '', 'default');
		$label =  "Toggle Transaction Type Debit/Credit" ;
		$this->HTML_LABEL_ROW = new HTML_ROW_LABELDecorator(  $data, $label );
		// label_row("Toggle Transaction Type Debit/Credit", submit("ToggleTransaction[$this->id]",_("ToggleTransaction"),false, '', 'default'));
	}
	function toHTML()
	{
		$this->HTML_LABEL_ROW->toHTML();
	}
}
