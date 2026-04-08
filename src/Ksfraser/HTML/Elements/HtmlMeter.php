<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * Semantic wrapper for <meter> tag.
 */
class HtmlMeter extends HtmlElement
{
    public function __construct(HtmlElementInterface $content)
    {
        parent::__construct($content);
        $this->setTag('meter');
    }
}
