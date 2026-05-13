<?php

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

/**
 * TransDate - Display transaction date row
 * 
 * Shows the transaction value date and entry timestamp.
 * Format: "Trans Date (Event Date): YYYY-MM-DD :: (YYYY-MM-DD HH:MM:SS)"
 * 
 * @package Views
 * @since 20251019 - Fixed $this->bi_lineitem bug, added use statements, return type hints, PHPDoc
 */
class TransDate implements HtmlElementInterface
{
	/**
	 * @var HTML_ROW_LABEL
	 */
	protected $row;
	
	/**
	 * Create transaction date row
	 * 
	 * @param object $bi_lineitem The bank import line item with valueTimestamp and entryTimestamp properties
	 */
	function __construct( $bi_lineitem )
	{
		// FIXED: Was $this->bi_lineitem (undefined property), now $bi_lineitem (parameter)
		// HTML_ROW_LABEL signature: ($data, $label, $width, $class)
		$this->row = new HTML_ROW_LABEL( 
			$bi_lineitem->valueTimestamp . " :: (" . $bi_lineitem->entryTimestamp . ")",  // data (content)
			"Trans Date (Event Date):",  // label
			null, 
			null 
		);
	}
	
	/**
	 * Get the HTML as a string
	 * 
	 * @return string The HTML
	 */
	function getHtml(): string
	{
		return $this->row->getHtml();
	}
	
	/**
	 * Output the HTML directly to screen
	 * 
	 * @return void
	 */
	function toHtml(): void
	{
		$this->row->toHtml();
	}
}
