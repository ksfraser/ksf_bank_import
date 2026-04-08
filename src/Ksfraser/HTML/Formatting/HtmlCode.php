<?php

namespace Ksfraser\HTML\Formatting;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlCode - semantic wrapper for <code>
 */
class HtmlCode extends HtmlElement {
    public function __construct(HtmlElementInterface $content) {
        parent::__construct($content);
        $this->setTag('code');
    }
}
