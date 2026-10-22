<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

/**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 */
class HTML_ROW_LABELDecorator implements HtmlElementInterface
{
	protected $HTML_LABEL_ROW;
	/**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
	 */
	function __construct( $data, $label, $width = 25, $class = 'label' )
	{
		$this->HTML_LABEL_ROW = new HTML_ROW_LABEL( $data, $label, $width, $class );
	}
	/**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
	 */
	function toHtml(): void
	{
		$this->HTML_LABEL_ROW->toHtml();
	}
	/**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
	 */
	function getHtml(): string
	{
		return $this->HTML_LABEL_ROW->getHtml();
	}
}
