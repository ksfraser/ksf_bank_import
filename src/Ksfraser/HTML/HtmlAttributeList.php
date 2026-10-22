<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class HtmlAttributeList implements HtmlElementInterface
{
	protected $attributeArray;
	
	/**
	 * Constructor
	 * 
	 * @param HtmlAttribute $attribute Initial attribute
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( HtmlAttribute $attribute )
	{
		$this->attributeArray = array(); // Initialize array
		$this->addAttribute( $attribute );
	}
	
	/**
	 * Add an attribute to the list
	 *
	 * If an attribute with the same name already exists it is replaced
	 * (HTML last-wins semantics), preventing duplicate attributes in output.
	 *
	 * @param HtmlAttribute $attribute Attribute to add
	 * @return void
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function addAttribute( HtmlAttribute $attribute ): void
	{
		foreach ( $this->attributeArray as $index => $existing )
		{
			if ( $existing->getName() === $attribute->getName() )
			{
				$this->attributeArray[ $index ] = $attribute;
				return;
			}
		}
		$this->attributeArray[] = $attribute;
	}
	
	/**
	 * Output HTML representation
	 * 
	 * @return void
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	public function toHtml(): void {
		echo $this->getHtml();
	}
	
	/**
	 * Get HTML representation as string
	 * 
	 * @return string HTML string of all attributes
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	public function getHtml(): string {
		$html = "";
		foreach( $this->attributeArray as $attribute )
		{
			$html .= $attribute->getHtml() . " ";
		}
		return $html;
	}
}
	

