<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class HtmlInternalCSSList extends HtmlStyleList 
{
 
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function getHtml(): string
	{
		if( count( $this->styleArray ) > 0 )
		{
			$html = $this->tag . '{';
		}
		else
		{
			return "";
		}
		foreach( $this->styleArray as $style )
		{
			$html .= $style->getHtml();
		}
		$html .= '}\n\r';
		return $html;
	}
}
