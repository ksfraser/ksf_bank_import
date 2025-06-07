<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

/**//*************************************************
* Class for generating forms
*
*	https://www.w3schools.com/tags/tag_input.asp
*
*
* Forms can have the following attributes
*	accept (FILE ONLY)
*	alt	text
*	autocomplete (on/off)
*	autofocus
*	checked (checkbox or RadioButton)
*	dirname
*	disabled
*	form (form_id)
*	formaction (submit or image - URL)
*	formenctype (submit, image)
*		application/x-www-form-urlencoded
*		multipart/form-data
*		text/plain
*	method (dialog, get, post)
*	name
*	novalidate
*	rel
*		external
*		help
*		license
*		next
*		nofollow
*		noopener
*		noreferrer
*		opener
*		prev
*		search
*	target
*		_blank
*		_self
*		_parent
*		_top
*******************************************************/
class HtmlInput extends HtmlElement
{
	//can have styles
	protected $action;	//URL
	protected $method;	//get or post
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "form";
	}
}
