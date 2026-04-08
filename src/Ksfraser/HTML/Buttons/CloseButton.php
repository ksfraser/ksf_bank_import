<?php

namespace Ksfraser\HTML\Buttons;

use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * Close Action Button Class
 * 
 * Specialized button for close actions (modals, dialogs, etc.).
 * Encapsulates close button styling and behavior.
 * 
 * Features:
 * - Secondary styling by default
 * - JavaScript function call support for custom close handlers
 * - Commonly used for modal/dialog dismissal
 * 
 * SOLID Principles:
 * - Single Responsibility: Only handles close button generation
 * - Open/Closed: Can be extended for custom close behaviors
 * 
 * @package Ksfraser\HTML
 * @author Kevin Fraser
 * @since 20251226
 * 
 * @example
 * ```php
 * $closeBtn = new CloseButton(new HtmlString('Close'));
 * $closeBtn->setOnclickFunction("closeModal();");
 * echo $closeBtn->getHtml();
 * ```
 */
class CloseButton extends ActionButton
{
    
    /**
     * Constructor
     * 
     * @param HtmlElementInterface|null $label The button label (default: "Close")
     */
    public function __construct(HtmlElementInterface $label = null)
    {
        if ($label === null) {
            $label = new HtmlString('Close');
        }
        parent::__construct('close', $label);
    }

    /**
     * Setup close button attributes
     *
     * @return void
     */
    protected function setupActionButton()
    {
        $this->setName('close_btn');
        $this->setCssClass('btn btn-secondary btn-sm');
    }


    /**
     * Add a CSS class to the button
     * @param string $class
     * @return self
     */
    public function addClass(string $class): self
    {
        $this->addCssClass($class);
        return $this;
    }
}
