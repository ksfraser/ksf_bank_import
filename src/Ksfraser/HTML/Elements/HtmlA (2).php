<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;
/**//****************************
* URL Links 
*
* <a href="URL">TEXT</a>
*/

class HtmlA extends HtmlElement
{
	function __construct( ?HtmlElementInterface $data = null )
	{
		parent::__construct( $data ?? new HtmlString( "" ) );
		$this->tag = "a";
	}
	/**
	 * Set href attribute
	 *
	 * @param string $url
	 * @return self
	 */
	public function setHref( string $url ): self
	{
		$this->setAttribute( 'href', $url );
		return $this;
	}

	/**
	 * Set link text (replaces nested content)
	 *
	 * @param string $text
	 * @return self
	 */
	public function setText( string $text ): self
	{
		$this->nested = array( new HtmlString( $text ) );
		return $this;
	}
	
	/**
	 * Set link content (replaces nested content)
	 *
	 * @param HtmlElementInterface $content
	 * @return self
	 */
	public function setContent( HtmlElementInterface $content ): self
	{
		$this->nested = array( $content );
		return $this;
	}
	function addHref( $url, $text = "" )
	{
		$this->setHref( (string)$url );
		if( $text instanceof HtmlElementInterface )
		{
			$this->setContent( $text );
			return;
		}
		if( is_string( $text ) && strlen( $text ) > 0 )
		{
			$this->setText( $text );
			return;
		}
		throw new \InvalidArgumentException( "An invalid HREF was passed in!" );
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
				$this->setAttribute( "target", (string)$target );
				break;
			default:
				throw new \InvalidArgumentException( "Target type not recognized: $target" );
		}
		return $this;
	}

}
