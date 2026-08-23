<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlAttributeList implements HtmlElementInterface
{
	protected $attributeArray;
	
	/**
	 * Constructor
	 * 
	 * @param HtmlAttribute $attribute Initial attribute
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
	 */
	public function toHtml(): void {
		echo $this->getHtml();
	}
	
	/**
	 * Get HTML representation as string
	 * 
	 * @return string HTML string of all attributes
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
	

