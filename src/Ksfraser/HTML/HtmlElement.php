<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

/**//***************************************************************************
* An HTML element is defined by a start tag, some content, and an end tag.
*
* https://www.w3schools.com/html/html_elements.asp
*
* Elements can have nested elements
*
* @since 20250517
*
*/
class HtmlElement implements HtmlElementInterface {
	protected $tag;
	protected $nested;
	protected $empty;	//Empty elements have no DATA and no closing tag
	protected $attributeList;	//ALL elements can have attributes
	
	function __construct( HtmlElementInterface $data )
	{
		//HTML is case insensitive.  XHTML etc requires lowercase.
		$this->nested = array();
		$this->addNested( $data );
		$this->attributes = array();
		$this->empty = false;
	}
	function addNested( HtmlElementInterface $element )
	{
		$this->nested[] = $element;
	}
	function addAttribute( HtmlAttribute $attribute )
	{
		$this->attributeList->addAttribute( $attribute );
	}
	function setAttributeList( HtmlAttributeList $list )
	{
		$this->attributeList = $list;
	}
	function newAttributeList()
	{
		$this->attributeList = new HtmlAttributeList( new HtmlAttribute( "", "") );
	}
	function setTag( $tag )
	{
		$this->tag = $tag;
	}

	/**
	 * Renders the object in HTML.
	 * The Html is echoed directly into the output.
	 */
	public function toHtml() {
		echo $this->getHtml();
	}
	public function getHtml()
	{
		$html = '<' . $this->tag;
		if( count( $this->attributes ) > 0 )
		{
			$html .=  $this->getAttributes();
		}
		$html .= '>';
		if( ! $this->empty )
		{
			if( isset( $this->children ) )
			{
				$html .= $this->renderChildrenHtml();
			}
			$html .= '</' . $this->tag . '>';
			return $html;
		}
	}
	protected function getAttributes()
	{
		$html = " ";
		foreach( $this->attributes as $attribute )
		{
			$html .= $attribute->getHtml() . " ";
		}
		return $html;
	}
}
