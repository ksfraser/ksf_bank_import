<?php

namespace Ksfraser\HTML\Composites;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HTML_ROW_LABEL;


/**
 * @BABOK FR-PROC-002, FR-IMP-002 | BR-PROC-001 | UAT-PROC-001
 */
class HTML_ROW_LABELDecorator implements HtmlElementInterface
{
	protected $HTML_LABEL_ROW;

	/**
 * @BABOK FR-PROC-002, FR-IMP-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $data, $label, $width = 25, $class = 'label' )
	{
		$this->HTML_LABEL_ROW = new HTML_ROW_LABEL( $data, $label, $width, $class );
	}

	/**
 * @BABOK FR-PROC-002, FR-IMP-002 | BR-PROC-001 | UAT-PROC-001
	 */
	public function toHtml(): void
	{
		$this->HTML_LABEL_ROW->toHtml();
	}

	/**
 * @BABOK FR-PROC-002, FR-IMP-002 | BR-PROC-001 | UAT-PROC-001
	 */
	public function getHtml(): string
	{
		return $this->HTML_LABEL_ROW->getHtml();
	}
}
