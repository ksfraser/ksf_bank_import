<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**//*********************************************
* Formatting on Text.
*
* @since 20250517
*/
/**
 * @BABOK FR-IMP-010, FR-PROC-009 | BR-IMP-001 | UAT-IMP-001
 */
class HtmlFormatting extends HtmlElement
{
	/**
 * @BABOK FR-IMP-010, FR-PROC-009 | BR-IMP-001 | UAT-IMP-001
	 */
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "";
	}
	/**
 * @BABOK FR-IMP-010, FR-PROC-009 | BR-IMP-001 | UAT-IMP-001
	 */
	function addAttribute( HtmlAttribute $attribute )
	{
		throw new Exception( "Does HTML Formatting allow Attributes?" );
	}
	/**
 * @BABOK FR-IMP-010, FR-PROC-009 | BR-IMP-001 | UAT-IMP-001
	 */
	function setAttributeList( HtmlAttributeList $list )
	{
		throw new Exception( "Does HTML Formatting allow Attributes?" );
	}
	/**
 * @BABOK FR-IMP-010, FR-PROC-009 | BR-IMP-001 | UAT-IMP-001
	 */
	function newAttributeList()
	{
		throw new Exception( "Does HTML Formatting allow Attributes?" );
	}
}
