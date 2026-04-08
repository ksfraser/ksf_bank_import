<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlArticle
 *
 * Represents the HTML <article> element for self-contained content.
 * Extends HtmlElement for standard element behavior.
 */
class HtmlArticle extends HtmlElement
{
    public function __construct(HtmlElementInterface $content)
    {
        parent::__construct($content);
        $this->setTag('article');
    }
}
