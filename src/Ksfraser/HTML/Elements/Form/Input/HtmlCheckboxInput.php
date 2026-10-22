<?php

namespace Ksfraser\HTML\Elements\Form\Input;

/**
 * Checkbox <input type="checkbox"> element
 *
 * @link https://www.w3schools.com/tags/tag_input.asp
 * @since 20260822
 * @BABOK FR-IMP-004, FR-005 | BR-IMP-002 | UAT-IMP-003
 */
class HtmlCheckboxInput extends HtmlInput
{
	/**
	 * Constructor
 * @BABOK FR-IMP-004, FR-005 | BR-IMP-002 | UAT-IMP-003
	 */
	public function __construct()
	{
		parent::__construct('checkbox');
	}
}
