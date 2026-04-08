<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * Semantic wrapper for <footer> tag.
 */
class HtmlFooter extends HtmlElement
{
    public function __construct(HtmlElementInterface $content)
    {
        parent::__construct($content);
        $this->setTag('footer');
    }
}
