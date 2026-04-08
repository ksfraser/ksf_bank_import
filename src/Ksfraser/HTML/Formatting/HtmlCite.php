<?php

namespace Ksfraser\HTML\Formatting;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlCite - semantic wrapper for <cite>
 */
class HtmlCite extends HtmlElement {
    public function __construct(HtmlElementInterface $content) {
        parent::__construct($content);
        $this->setTag('cite');
    }
}
