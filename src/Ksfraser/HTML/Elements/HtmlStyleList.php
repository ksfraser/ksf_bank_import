<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**//**
* A Style is an attribute with KEY Style and value param:setting
*
*	https://www.w3schools.com/html/html_styles.asp
*
*	Examples
*		background-color
*		color
*		font-family
*		font-size
*		text-align
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
class HtmlStyleList implements HtmlElementInterface
{
	protected $styleArray;
        /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
         */
        function __constructor( HtmlStyle $style )
        {
                $this->addStyle( $style );
        }
        /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
         */
        function addStyle( HtmlStyle $style )
        {
                $this->styleArray[] = $style;
        }
        /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
         */
        public function toHtml() {
                echo $this->getHtml();
        }
 
	/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function getHtml()
	{
		if( count( $this->styleArray ) > 0 )
		{
			$html = 'style="';
		}
		else
		{
			return "";
		}
		foreach( $this->styleArray as $style )
		{
			$html .= $style->getHtml();
		}
		$html .= '"';
		return $html;
	}
}
