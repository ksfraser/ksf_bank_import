<?php

namespace Ksfraser\HTML\Elements\Form\Input;

/**
 * Checkbox <input type="checkbox"> element
 *
 * @link https://www.w3schools.com/tags/tag_input.asp
 * @since 20260822
 */
class HtmlCheckboxInput extends HtmlInput
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct('checkbox');
	}
}
