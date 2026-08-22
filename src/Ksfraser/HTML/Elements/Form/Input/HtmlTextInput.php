<?php

namespace Ksfraser\HTML\Elements\Form\Input;

/**
 * Text box <input type="text"> element
 *
 * @link https://www.w3schools.com/tags/tag_input.asp
 * @since 20260822
 */
class HtmlTextInput extends HtmlInput
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct('text');
	}
}
