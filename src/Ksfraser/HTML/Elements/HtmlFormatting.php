<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\HtmlAttributeList;

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
	function addAttribute( HtmlAttribute $attribute ): void
	{
		throw new Exception( "Does HTML Formatting allow Attributes?" );
	}
	/**
 * @BABOK FR-IMP-010, FR-PROC-009 | BR-IMP-001 | UAT-IMP-001
	 */
	function setAttributeList( HtmlAttributeList $list ): void
	{
		throw new Exception( "Does HTML Formatting allow Attributes?" );
	}
	/**
 * @BABOK FR-IMP-010, FR-PROC-009 | BR-IMP-001 | UAT-IMP-001
	 */
	function newAttributeList(): void
	{
		throw new Exception( "Does HTML Formatting allow Attributes?" );
	}
}
