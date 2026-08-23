<?php

namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\Composites\HTML_LABEL_ROW;
use Ksfraser\HTML\Composites\HTML_ROW_LABELDecorator;
//require_once( __DIR__ . "/HTML/HTML_ROW_LABELDecorator.php" );


//TODO: Refactor to replace the Submit button with our own class.

class ToggleTransactionTypeButton implements \Ksfraser\HTML\HtmlElementInterface
{
	protected $HTML_LABEL_ROW;
	function __construct( int $index )
	{
		$buttonLabel = new \Ksfraser\HTML\Elements\HtmlString(_("ToggleTransaction"));
		$submitButton = new \Ksfraser\HTML\Elements\HtmlSubmit($buttonLabel);
		$submitButton->setName("ToggleTransaction[$index]");
		$submitButton->setClass("default");
		$label =  "Toggle Transaction Type Debit/Credit" ;
		$this->HTML_LABEL_ROW = new HTML_ROW_LABELDecorator(  $submitButton, $label );
	}
	function toHtml(): void
	{
		$this->HTML_LABEL_ROW->toHtml();
	}
	function getHtml(): string
	{
		return $this->HTML_LABEL_ROW->getHtml();
	}
}
