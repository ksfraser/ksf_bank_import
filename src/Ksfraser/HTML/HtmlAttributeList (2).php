<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlAttributeList implements HtmlElementInterface
{
	protected $attributeArray;
	
	       /**
		* Constructor
		* @param HtmlAttribute|null $attribute Optional attribute to add on creation
		*/
		       function __construct($attribute = null)
		       {
			       $this->attributeArray = array(); // Initialize array
			       if ($attribute !== null) {
					   $this->addAttributeObject($attribute);
			       }
		       }
	
	/**
	 * Add an attribute to the list
	 * 
	 * @param HtmlAttribute $attribute Attribute to add
	 * @return void
	 */
		function addAttributeObject($attribute): void
	       {
		       if (!($attribute instanceof HtmlAttribute)) {
			       throw new \InvalidArgumentException("HtmlAttributeList only accepts HtmlAttribute objects.");
		       }
		       $this->attributeArray[] = $attribute;
		       return;
	       }

	/**
	 * Set (add or replace) an attribute in the list.
	 *
	 * If an attribute with the same name already exists, it is replaced.
	 *
	 * @param HtmlAttribute $attribute
	 * @return void
	 */
	public function setAttribute( HtmlAttribute $attribute ): void
	{
		$name = $attribute->getName();
		foreach( $this->attributeArray as $idx => $existing )
		{
			if( method_exists( $existing, 'getName' ) && $existing->getName() === $name )
			{
				$this->attributeArray[$idx] = $attribute;
				return;
			}
		}
		$this->addAttributeObject( $attribute );
		return;
	}

	/**
	 * Get an attribute value by name.
	 *
	 * @param string $name
	 * @return string|null
	 */
	public function getAttributeValue( string $name ): ?string
	{
		foreach( $this->attributeArray as $existing )
		{
			if( method_exists( $existing, 'getName' ) && $existing->getName() === $name )
			{
				return method_exists( $existing, 'getValue' ) ? $existing->getValue() : null;
			}
		}
		return null;
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
	

