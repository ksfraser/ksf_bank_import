<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlAttribute;

/**//****************************
* Links 
*
* <a href="URL">TEXT</a>
*/
class HtmlLink extends HtmlElement
{
	//can have styles, title
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "a";
	}
	function addHref( $url, $text = "" )
	{
		// If text is provided (backwards compatibility)
		if( is_object( $text ) )
		{
			// Object passed, leave data as is
		}
		else if( is_string( $text ) AND strlen( $text ) > 0 )
		{
			// String passed, wrap in HtmlString
			$this->data = new HtmlString( $text );
		}
		// If no text provided, leave data as already set by constructor
		
		// Set the href attribute
		$this->addAttribute( new HtmlAttribute( "href", $url ) );
	}
	function setTarget( $target )
	{
		//Target can be _self, _blank, _parent, _top
		switch( $target )
		{
			case '_self':
			case '_blank':
			case '_parent':
			case '_top':
				$this->addAttribute( new HtmlAttribute( "target", $target ) );
				break;
			default:
				throw new \Exception( "Target type not recognized: $target" );
		}
		return;
	}

}
