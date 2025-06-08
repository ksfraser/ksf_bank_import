<?php

namespace Ksfraser\HTML;

//use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlTableRow;
use Ksfraser\HTML\HtmlString;
use Ksfraser\HTML\HtmlAttribute;
require_once( __DIR__ . '/HtmlString.php' );
require_once( __DIR__ . '/HtmlAttribute.php' );


class HTML_ROW_LABEL extends HtmlTableRow
{
/*
	function __construct( $data )
	{
		if( ! is_object( $data ) )
		{
			$obj = HtmlString( $data );
		}
		else
		{
			$obj = $data;
		}
		parent::__construct( $obj );
	}
*/
	function __construct( $data, $label, $width = 25, $class = 'label' )
	{
		if( ! is_object( $data ) )
		{
			$obj = new HtmlString( $data );
		}
		else
		{
			$obj = $data;
		}
		parent::__construct( $obj );
		$this->addAttribute( new HtmlAttribute( "label", $label ) );
		$this->addAttribute( new HtmlAttribute( "width", $width ) );
		$this->addAttribute( new HtmlAttribute( "class", $class ) );
	}
}

