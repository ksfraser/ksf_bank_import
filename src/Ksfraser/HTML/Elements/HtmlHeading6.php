<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**//****************************
* Headings can have 6 levels, styles.
*
* @since 20250517
*/
/**
 * @BABOK FR-IMP-010, FR-UPL-001 | BR-IMP-001 | UAT-IMP-001
 */
class HtmlHeading6 extends HtmlHeading
{
	/**
 * @BABOK FR-IMP-010, FR-UPL-001 | BR-IMP-001 | UAT-IMP-001
	 */
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "h6";
	}
}
