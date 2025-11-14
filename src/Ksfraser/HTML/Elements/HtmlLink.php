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
	/**
	 * @var array Query parameters to append to URL
	 */
	protected $params = [];
	
	/**
	 * @var string Base URL without query parameters
	 */
	protected $baseUrl = '';
	
	/**
	 * Constructor - creates an <a> link element
	 * 
	 * @param HtmlElementInterface $data Initial nested content
	 */
	function __construct(HtmlElementInterface $data)
	{
		parent::__construct($data);
		$this->tag = "a";
	}
	
	/**
	 * Add href attribute to the link, optionally with link text
	 * 
	 * @param string $url The URL for the link
	 * @param string|HtmlElementInterface $text Optional link text or element
	 * @return void
	 */
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
		
		// Store base URL for later param appending
		$this->baseUrl = $url;
		
		// Set the href attribute (will be updated if params are added)
		$this->updateHref();
	}
	
	/**
	 * Add a query parameter to the URL
	 * 
	 * @param string $key Parameter name
	 * @param string $value Parameter value
	 * @return self For method chaining
	 */
	function addParam( string $key, string $value ): self
	{
		$this->params[$key] = $value;
		$this->updateHref();
		return $this;
	}
	
	/**
	 * Update the href attribute with base URL + query params
	 * 
	 * @return void
	 */
	protected function updateHref(): void
	{
		$url = $this->baseUrl;
		
		if( !empty($this->params) )
		{
			$url .= '?' . http_build_query($this->params);
		}
		
		$this->addAttribute( new HtmlAttribute( "href", $url ) );
	}
	/**
	 * Set the target attribute for the link
	 * 
	 * @param string $target Target type (_self, _blank, _parent, _top)
	 * @return void
	 * @throws \Exception If target type is not recognized
	 */
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
