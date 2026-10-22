<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-VAL-005, FR-PROC-009 | BR-VAL-003 | UAT-VAL-004
 */
class HtmlImage extends HtmlElement
{
	//can have style, alt.  MUST HAVE src
	//  Width and Height can be either part of Style, or attributes themselves
	/**
 * @BABOK FR-VAL-005, FR-PROC-009 | BR-VAL-003 | UAT-VAL-004
	 */
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "img";
	}
	/**
 * @BABOK FR-VAL-005, FR-PROC-009 | BR-VAL-003 | UAT-VAL-004
	 */
	function setSrc( $src )
	{
		$this->addAttribute( new HtmlAttribute( "src", $src ) );
	}
	/**
 * @BABOK FR-VAL-005, FR-PROC-009 | BR-VAL-003 | UAT-VAL-004
	 */
	function setAlt( $alt )
	{
		$this->addAttribute( new HtmlAttribute( "alt", $alt ) );
	}
	// Either attributes or STYLE can be used to set the size
	//	However stylesheets can be used to change the size
	//	except when we use STYLE (see set size below)
	/**
 * @BABOK FR-VAL-005, FR-PROC-009 | BR-VAL-003 | UAT-VAL-004
	 */
	function setHeight( $height )
	{
		$this->addAttribute( new HtmlAttribute( "height", $height ) );
	}
	/**
 * @BABOK FR-VAL-005, FR-PROC-009 | BR-VAL-003 | UAT-VAL-004
	 */
	function setWidth( $width )
	{
		$this->addAttribute( new HtmlAttribute( "width", $width ) );
	}
	/**
 * @BABOK FR-VAL-005, FR-PROC-009 | BR-VAL-003 | UAT-VAL-004
	 */
	function setSize( int $width, int $height )
	{
		$w = new HtmlStyle( "width", $width . "px" );
		$h = new HtmlStyle( "height", $height . "px" );
		$s = new HtmlStyleList( $w );
		$s->addAttribute( $h );
		$this->addAttribute( 'style', $s );
	}
}
