<?php

namespace Ksfraser\HTML\Elements\Form\Input;

/**
 * Text box <input type="text"> element
 *
 * @link https://www.w3schools.com/tags/tag_input.asp
 * @since 20260822
 * @BABOK FR-IMP-002, FR-PROC-002 | BR-IMP-001 | UAT-IMP-001
 */
class HtmlTextInput extends HtmlInput
{
	/**
	 * Constructor
 * @BABOK FR-IMP-002, FR-PROC-002 | BR-IMP-001 | UAT-IMP-001
	 */
	public function __construct()
	{
		parent::__construct('text');
	}
}
