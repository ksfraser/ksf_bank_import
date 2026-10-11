<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlAttributeList implements HtmlElementInterface
{
	protected $attributeArray;
	
	/**
	 * Constructor
	 * 
	 * @param HtmlAttribute|null $attribute Optional initial attribute
	 */
	function __construct( ?HtmlAttribute $attribute = null )
	{
		$this->attributeArray = array(); // Initialize array
		if ($attribute !== null) {
			$this->addAttribute( $attribute );
		}
	}
	
	/**
	 * Add an attribute to the list
	 * 
	 * @param HtmlAttribute $attribute Attribute to add
	 * @return void
	 */
	function addAttribute( HtmlAttribute $attribute ): void
	{
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
	

