<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * Semantic wrapper for <figure> tag.
 */
class HtmlFigure extends HtmlElement
{
    public function __construct(HtmlElementInterface $content)
    {
        parent::__construct($content);
        $this->setTag('figure');
    }
}
