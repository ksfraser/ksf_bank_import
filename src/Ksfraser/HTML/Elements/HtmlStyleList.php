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
        function __construct( ?HtmlStyle $style = null )
        {
                $this->styleArray = array();
                if( $style !== null )
                {
                        $this->addStyle( $style );
                }
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
 * Get the inner CSS declarations for the styles in the list,
 * without the style=" " attribute wrapper.
         */
        function getStylesString(): string
        {
                $html = "";
                foreach( $this->styleArray as $style )
                {
                        $html .= $style->getHtml();
                }
                return $html;
        }
        /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
         */
        public function toHtml(): void {
                echo $this->getHtml();
        }
 
	/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function getHtml(): string
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
