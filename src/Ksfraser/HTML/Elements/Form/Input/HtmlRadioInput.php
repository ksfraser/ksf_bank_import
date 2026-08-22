<?php

namespace Ksfraser\HTML\Elements\Form\Input;

/**
 * Radio button <input type="radio"> element
 *
 * @link https://www.w3schools.com/tags/tag_input.asp
 * @since 20260822
 */
class HtmlRadioInput extends HtmlInput
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct('radio');
	}
}
