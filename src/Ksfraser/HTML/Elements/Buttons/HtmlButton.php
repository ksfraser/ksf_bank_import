<?php

namespace Ksfraser\HTML\Elements\Buttons;

use Ksfraser\HTML\HtmlAttribute;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**//****************************************
* Buttons use Javascript
*
* @since 20250517
*/
/**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 */
class HtmlButton extends HtmlElement
{
	//can have style, alt.  MUST HAVE src
	/**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
	 */
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "button";
	}
	/**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
	 */
	function setOnclick( HtnlAttribute $onclick )
	{
			//onclick="document.location='default.asp'"
		$this->addAttribute( new HtmlAttribute( "onclick", $onclick ) );
	}
}
