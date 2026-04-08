<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlAddress
 *
 * Represents the HTML <address> element for contact information.
 * Extends HtmlElement for standard element behavior.
 */
class HtmlAddress extends HtmlElement
{
    public function __construct(HtmlElementInterface $content)
    {
        parent::__construct($content);
        $this->setTag('address');
    }
}
