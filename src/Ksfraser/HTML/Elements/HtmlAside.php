<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlAside
 *
 * Represents the HTML <aside> element for tangential content.
 * Extends HtmlElement for standard element behavior.
 */
class HtmlAside extends HtmlElement
{
    public function __construct(HtmlElementInterface $content)
    {
        parent::__construct($content);
        $this->setTag('aside');
    }
}
