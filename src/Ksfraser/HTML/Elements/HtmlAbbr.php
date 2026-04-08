<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlAbbr
 *
 * Represents the HTML <abbr> element for abbreviations and acronyms.
 * Extends HtmlElement for standard element behavior.
 */
class HtmlAbbr extends HtmlElement
{
    public function __construct(HtmlElementInterface $content)
    {
        parent::__construct($content);
        $this->setTag('abbr');
    }
}
