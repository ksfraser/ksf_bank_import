<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

/**
 * Semantic wrapper for <dfn> tag.
 */
use Ksfraser\HTML\HtmlElementInterface;

class HtmlDfn extends HtmlElement
{
    public function __construct(HtmlElementInterface $content)
    {
        parent::__construct($content);
        $this->setTag('dfn');
    }
}
