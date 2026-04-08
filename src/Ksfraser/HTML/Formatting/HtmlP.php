<?php
namespace Ksfraser\HTML\Formatting;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * Semantic wrapper for <p> tag.
 */
class HtmlP extends HtmlElement
{
    public function __construct(HtmlElementInterface $content)
    {
        parent::__construct($content);
        $this->setTag('p');
    }
}
