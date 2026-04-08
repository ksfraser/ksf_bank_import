<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * Semantic wrapper for <menu> tag.
 */
class HtmlMenu extends HtmlElement
{
    public function __construct(HtmlElementInterface $content)
    {
        parent::__construct($content);
        $this->setTag('menu');
    }
}
