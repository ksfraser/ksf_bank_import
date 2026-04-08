<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlElement;

/**
 * HtmlBlockquote - semantic wrapper for <blockquote>
 */
class HtmlBlockquote extends HtmlElement {
    public function __construct(HtmlElementInterface $content) {
        parent::__construct($content);
        $this->setTag('blockquote');
    }
}
