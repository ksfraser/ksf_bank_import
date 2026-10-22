<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-VAL-004, FR-PROC-009 | BR-VAL-004 | UAT-PROC-008
 */

/**
 * @BABOK FR-VAL-004, FR-PROC-009 | BR-VAL-004 | UAT-VAL-006
 */

class MatchingGLS implements HtmlElementInterface
{
	protected $row;
	/**
 * @BABOK FR-VAL-004, FR-PROC-009 | BR-VAL-004 | UAT-VAL-006
	 */
	function __construct( $bi_lineitem )
	{
		$data = new MatchingGLFactory( $bi_lineitem );
		$label = "Matching GLs:";
		$this->row = new HTML_ROW_LABEL( $data, $label,  null, null );
	}
	/**
 * @BABOK FR-VAL-004, FR-PROC-009 | BR-VAL-004 | UAT-VAL-006
	 */
	function getHtml(): string
	{
		return $this->row->getHtml();
	}
	/**
 * @BABOK FR-VAL-004, FR-PROC-009 | BR-VAL-004 | UAT-VAL-006
	 */
	function toHtml(): void
	{
		$this->row->toHtml();
	}
}

/**
 * @BABOK FR-VAL-004 | BR-VAL-004
 */
