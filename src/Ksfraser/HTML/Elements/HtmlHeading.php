<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**//****************************
* Headings can have 6 levels, styles.
*
*/
/**
 * @BABOK FR-IMP-010, FR-UPL-001 | BR-IMP-001 | UAT-IMP-001
 */
class HtmlHeading extends HtmlElement
{
	//can have styles
	/**
 * @BABOK FR-IMP-010, FR-UPL-001 | BR-IMP-001 | UAT-IMP-001
	 */
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
	}
}
