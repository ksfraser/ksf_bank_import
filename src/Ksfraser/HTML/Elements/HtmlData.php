<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlData - semantic wrapper for <data>
 */
class HtmlData extends HtmlElement {
    public function __construct(HtmlElementInterface $content) {
        parent::__construct($content);
        $this->setTag('data');
    }
}
