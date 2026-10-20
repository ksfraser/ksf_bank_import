<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

/**
 * @BABOK CLASS ROLLUP: TransDate — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/TransDate.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
	 * Create transaction date row
	 * 
	 * @param object $bi_lineitem The bank import line item with valueTimestamp and entryTimestamp properties
	 */
	function __construct( $bi_lineitem )
	{
		// FIXED: Was $this->bi_lineitem (undefined property), now $bi_lineitem (parameter)
		// HTML_ROW_LABEL signature: ($data, $label, $width, $class)
		$this->row = new HTML_ROW_LABEL( 
			$bi_lineitem->getValueTimestamp() . " :: (" . $bi_lineitem->getEntryTimestamp() . ")",  // data (content)
			"Trans Date (Event Date):",  // label
			null, 
			null 
		);
	}
	
	/**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml
	 * Get the HTML as a string
	 * 
	 * @return string The HTML
	 */
	function getHtml(): string
	{
		return $this->row->getHtml();
	}
	
	/**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: toHtml
	 * Output the HTML directly to screen
	 * 
	 * @return void
	 */
	function toHtml(): void
	{
		$this->row->toHtml();
	}
}
