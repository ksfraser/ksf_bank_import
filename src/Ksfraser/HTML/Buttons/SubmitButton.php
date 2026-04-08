<?php

namespace Ksfraser\HTML\Buttons;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * Submit Action Button Class
 * 
 * Specialized button for form submission.
 * Encapsulates submit button styling and behavior.
 * 
 * Features:
 * - Primary styling by default (blue background)
 * - Type="submit" for form submission
 * - JavaScript function call support for validation
 * 
 * SOLID Principles:
 * - Single Responsibility: Only handles submit button generation
 * - Open/Closed: Can be extended for custom submit behaviors
 * 
 * @package Ksfraser\HTML
 * @author Kevin Fraser
 * @since 20251226
 * 
 * @example
 * ```php
 * $submitBtn = new SubmitButton(new HtmlString('Save'));
 * $submitBtn->setOnclickFunction("return validateForm();");
 * echo $submitBtn->getHtml();
 * ```
 */
class SubmitButton extends ActionButton
{
    /**
     * Constructor
     * 
     * @param HtmlElementInterface|null $label The button label (default: "Submit")
     */
    public function __construct(HtmlElementInterface $label = null)
    {
        if ($label === null) {
            $label = new HtmlString('Submit');
        }
        parent::__construct('submit', $label);
    }

    /**
     * Setup submit button attributes
     *
     * @return void
     */
    protected function setupActionButton()
    {
        $this->setName('submit_btn');
        $this->setType('submit');
        $this->setCssClass('btn btn-primary btn-sm');
    }

}
