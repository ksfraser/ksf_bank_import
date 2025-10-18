<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlAttributeList implements HtmlElementInterface
{
	protected $attributeArray;
	function __constructor( HtmlAttribute $attribute )
	{
		$this->addAttribute( $attribute );
	}
	function addAttribute( HtmlAttribute $attribute )
	{
		$this->attributeArray[] = $attribute;
	}
	public function toHtml() {
		echo $this->getHtml();
	}
	public function getHtml() {
		$html = "";
		foreach( $this->attributeArray as $attribute )
		{
			$html .= $attribute->getHtml() . " ";
		}
		return $html;
	}
}
	
